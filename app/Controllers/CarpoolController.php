<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use PDO;
use Slim\Views\Twig;
use MongoDB\Collection;

class CarpoolController
{
    protected Twig $view;
    protected PDO $db;
    protected Collection $prefsCollection;

    public function __construct(ContainerInterface $container)
    {
        $this->view             = $container->get('view');
        $this->db               = $container->get('db');
        $this->prefsCollection = $container->get('prefsCollection');
    }

    public function listAvailable(Request $request, Response $response): Response
    {
        $params   = $request->getQueryParams();
        $pickup   = $params['pickup'] ?? null;
        $dropoff  = $params['dropoff'] ?? null;
        $minSeats = $params['min_seats'] ?? null;
        $energy   = $params['energy'] ?? null;
        $eco      = $params['eco'] ?? null;

        $sql = "
            SELECT c.*, u.name AS driver_name, v.energy_type
            FROM carpools c
            JOIN users u ON c.driver_id = u.id
            JOIN vehicles v ON c.vehicle_id = v.id
            WHERE c.status = 'upcoming'
              AND (c.total_seats - c.occupied_seats) > 0
        ";

        $conditions = [];
        $values     = [];

        if ($pickup) {
            $conditions[] = 'c.pickup_location LIKE ?';
            $values[]     = "%$pickup%";
        }

        if ($dropoff) {
            $conditions[] = 'c.dropoff_location LIKE ?';
            $values[]     = "%$dropoff%";
        }

        if ($minSeats) {
            $conditions[] = '(c.total_seats - c.occupied_seats) >= ?';
            $values[]     = (int)$minSeats;
        }

        if ($energy) {
            $conditions[] = 'v.energy_type = ?';
            $values[]     = $energy;
        }

        if ($eco === '1') {
            $conditions[] = "(v.energy_type IN ('electric','hybrid'))";
        }

        if (!empty($conditions)) {
            $sql .= ' AND ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY c.departure_time ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        $carpools = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view->render($response, 'carpool-list.twig', [
            'carpools' => $carpools,
            'filters'  => compact('pickup', 'dropoff', 'minSeats', 'energy', 'eco'),
        ]);
    }

    public function viewDetail(Request $request, Response $response, array $args): Response
    {
        $carpoolId = (int)$args['id'];

        $stmt = $this->db->prepare("
            SELECT c.*, u.name AS driver_name, u.driver_rating, v.make, v.model, v.energy_type
            FROM carpools c
            JOIN users u ON c.driver_id = u.id
            JOIN vehicles v ON c.vehicle_id = v.id
            WHERE c.id = ?
        ");
        $stmt->execute([$carpoolId]);
        $carpool = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$carpool) {
            $response->getBody()->write("Carpool not found.");
            return $response->withStatus(404);
        }

        $preferences = null;
        $driverId = (int)$carpool['driver_id'];
        $mongoResult = $this->prefsCollection->findOne(['user_id' => $driverId]);
        if ($mongoResult && isset($mongoResult['preferences'])) {
            $preferences = json_decode(json_encode($mongoResult['preferences']), true);
        }

        return $this->view->render($response, 'carpool-detail.twig', [
            'carpool'     => $carpool,
            'preferences' => $preferences,
        ]);
    }

    public function joinCarpool(Request $request, Response $response, array $args): Response
    {
        // Ensure user is authenticated
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $carpoolId = (int)$args['id'];
        $userId    = $_SESSION['user']['id'] ?? null;

        if (!$userId) {
            // Redirect guests to register/login before joining
            return $response
                ->withHeader('Location', "/register?redirect=/carpools/{$carpoolId}")
                ->withStatus(302);
        }

        $data           = $request->getParsedBody();
        $requestedSeats = max(1, (int)($data['passenger_count'] ?? 1));
        $costPerSeat    = 5;
        $totalCost      = $requestedSeats * $costPerSeat;

        // Load carpool
        $stmt = $this->db->prepare("SELECT * FROM carpools WHERE id = ?");
        $stmt->execute([$carpoolId]);
        $carpool = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$carpool) {
            $response->getBody()->write("Carpool not found.");
            return $response->withStatus(404);
        }

        // Check seat availability
        $availableSeats = $carpool['total_seats'] - $carpool['occupied_seats'];
        if ($requestedSeats > $availableSeats) {
            return $this->reloadCarpoolWithMessage(
                $response,
                $carpoolId,
                "Not enough available seats. Only {$availableSeats} left."
            );
        }

        // Check user credits
        $stmt = $this->db->prepare("SELECT credits FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || $user['credits'] < $totalCost) {
            return $this->reloadCarpoolWithMessage(
                $response,
                $carpoolId,
                "You need {$totalCost} credits to join. You have " . ($user['credits'] ?? 0) . "."
            );
        }

        // Prevent duplicate join
        $stmt = $this->db->prepare("
            SELECT id
              FROM ride_requests
             WHERE passenger_id = ?
               AND carpool_id   = ?
        ");
        $stmt->execute([$userId, $carpoolId]);
        if ($stmt->fetch()) {
            return $this->reloadCarpoolWithMessage(
                $response,
                $carpoolId,
                "You have already joined this ride."
            );
        }

        // Create ride request
        $this->db->prepare("
            INSERT INTO ride_requests
                (passenger_id, driver_id, carpool_id, pickup_location, dropoff_location, passenger_count, status, created_at)
            VALUES
                (?, ?, ?, ?, ?, ?, 'accepted', NOW())
        ")->execute([
            $userId,
            $carpool['driver_id'],
            $carpoolId,
            $carpool['pickup_location'],
            $carpool['dropoff_location'],
            $requestedSeats
        ]);

        // Update seats and deduct credits
        $this->db->prepare("
            UPDATE carpools
               SET occupied_seats = occupied_seats + ?
             WHERE id             = ?
        ")->execute([$requestedSeats, $carpoolId]);

        $this->db->prepare("
            UPDATE users
               SET credits = credits - ?
             WHERE id      = ?
        ")->execute([$totalCost, $userId]);

        return $this->reloadCarpoolWithMessage(
            $response,
            $carpoolId,
            "Successfully joined. {$totalCost} credits deducted."
        );
    }


    public function startCarpool(Request $request, Response $response, array $args): Response
    {
        $carpoolId = (int)$args['id'];

        $stmt = $this->db->prepare("SELECT * FROM carpools WHERE id = ?");
        $stmt->execute([$carpoolId]);
        $carpool = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$carpool) {
            $response->getBody()->write("Carpool not found.");
            return $response->withStatus(404);
        }

        if ((int)$carpool['occupied_seats'] === 0) {
            $response->getBody()->write("Cannot start ride with 0 passengers.");
            return $response->withStatus(400);
        }

        $this->db->prepare("UPDATE carpools SET status = 'in progress' WHERE id = ?")->execute([$carpoolId]);

        return $response->withHeader('Location', '/driver/dashboard')->withStatus(302);
    }

    public function completeCarpool(Request $request, Response $response, array $args): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $driverId  = $_SESSION['user']['id'] ?? null;
        $carpoolId = (int)$args['id'];

        try {
            $stmt = $this->db->prepare("SELECT * FROM carpools WHERE id = :id AND driver_id = :driver_id");
            $stmt->execute(['id' => $carpoolId, 'driver_id' => $driverId]);
            $carpool = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$carpool || $carpool['status'] !== 'in progress') {
                return $response->withStatus(403);
            }

            $this->db->beginTransaction();

            $this->db->prepare("UPDATE carpools SET status = 'completed' WHERE id = :id")
                ->execute(['id' => $carpoolId]);

            $this->db->prepare("UPDATE ride_requests SET status = 'completed' WHERE carpool_id = :id AND status = 'accepted'")
                ->execute(['id' => $carpoolId]);

            $stmt = $this->db->prepare("SELECT passenger_id FROM ride_requests WHERE carpool_id = :id AND status = 'completed'");
            $stmt->execute(['id' => $carpoolId]);
            $passengers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($passengers as $passenger) {
                $this->db->prepare("UPDATE users SET credits = credits + 10 WHERE id = :id")
                    ->execute(['id' => $passenger['passenger_id']]);
            }

            $this->db->commit();
            return $response->withHeader('Location', '/driver/dashboard')->withStatus(302);
        } catch (\PDOException $e) {
            $this->db->rollBack();
            $response->getBody()->write(json_encode(['error' => 'Database error', 'details' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function createForm(Request $request, Response $response): Response
    {
        $userId = $_SESSION['user']['id'] ?? null;
        $stmt = $this->db->prepare("SELECT * FROM vehicles WHERE driver_id = ?");
        $stmt->execute([$userId]);
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view->render($response, 'carpool-create.twig', [
            'vehicles' => $vehicles
        ]);
    }

    public function storeCarpool(Request $request, Response $response): Response
    {
        $data   = $request->getParsedBody();
        $userId = $_SESSION['user']['id'] ?? null;

        $stmt = $this->db->prepare("
            INSERT INTO carpools (driver_id, vehicle_id, pickup_location, dropoff_location, departure_time, total_seats, occupied_seats, status)
            VALUES (?, ?, ?, ?, ?, ?, 0, 'upcoming')
        ");
        $stmt->execute([
            $userId,
            $data['vehicle_id'],
            $data['pickup_location'],
            $data['dropoff_location'],
            $data['departure_time'],
            $data['total_seats']
        ]);

        return $response->withHeader('Location', '/carpools')->withStatus(302);
    }

    private function reloadCarpoolWithMessage(Response $response, int $carpoolId, string $joinMessage): Response
    {
        $stmt = $this->db->prepare("
            SELECT c.*, u.name AS driver_name, u.driver_rating, v.make, v.model, v.energy_type
            FROM carpools c
            JOIN users u ON c.driver_id = u.id
            JOIN vehicles v ON c.vehicle_id = v.id
            WHERE c.id = ?
        ");
        $stmt->execute([$carpoolId]);
        $carpool = $stmt->fetch(PDO::FETCH_ASSOC);

        $preferences = null;
        if ($carpool) {
            $prefDoc = $this->prefsCollection->findOne(['user_id' => (int)$carpool['driver_id']]);
            if ($prefDoc && isset($prefDoc['preferences'])) {
                $preferences = json_decode(json_encode($prefDoc['preferences']), true);
            }
        }

        return $this->view->render($response, 'carpool-detail.twig', [
            'carpool'      => $carpool,
            'preferences'  => $preferences,
            'join_message' => $joinMessage,
        ]);
    }
}
