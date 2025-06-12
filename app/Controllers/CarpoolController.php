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
        $this->view           = $container->get('view');
        $this->db             = $container->get('db');
        $this->prefsCollection = $container->get('prefsCollection');
    }

    /**
     * Display all available carpools
     */
    public function listAvailable(Request $request, Response $response): Response
    {
        $params    = $request->getQueryParams();
        $pickup    = $params['pickup']    ?? null;
        $dropoff   = $params['dropoff']   ?? null;
        $minSeats  = $params['min_seats'] ?? null;
        $energy    = $params['energy']    ?? null;
        $eco       = $params['eco']       ?? null;

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

    /**
     * Show details for a specific carpool
     */
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

        $driverId    = (int)$carpool['driver_id'];
        $mongoResult = $this->prefsCollection->findOne(['user_id' => $driverId]);

        $preferences = null;
        if ($mongoResult && isset($mongoResult['preferences'])) {
            $preferences = json_decode(json_encode($mongoResult['preferences']), true);
        }

        return $this->view->render($response, 'carpool-detail.twig', [
            'carpool'     => $carpool,
            'preferences' => $preferences,
        ]);
    }

    /**
     * Join an existing carpool
     */
    public function joinCarpool(Request $request, Response $response, array $args): Response
    {
        $carpoolId     = (int)$args['id'];
        $userId        = $_SESSION['user']['id'] ?? null;
        $data          = $request->getParsedBody();
        $requestedSeats = max(1, (int)$data['passenger_count']);
        $costPerSeat    = 5;
        $totalCost      = $requestedSeats * $costPerSeat;

        // Fetch carpool
        $stmt     = $this->db->prepare("SELECT * FROM carpools WHERE id = ?");
        $stmt->execute([$carpoolId]);
        $carpool = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$carpool) {
            $response->getBody()->write("Carpool not found.");
            return $response->withStatus(404);
        }

        // Check seats
        $availableSeats = $carpool['total_seats'] - $carpool['occupied_seats'];
        if ($requestedSeats > $availableSeats) {
            return $this->reloadCarpoolWithMessage(
                $response,
                $carpoolId,
                "Not enough available seats. Only $availableSeats left."
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
                "You need $totalCost credits to join. You have " . ($user['credits'] ?? 0) . "."
            );
        }

        // Prevent duplicates
        $stmt->execute([$userId, $carpoolId]);
        if ($stmt->fetch()) {
            return $this->reloadCarpoolWithMessage(
                $response,
                $carpoolId,
                "You have already joined this ride."
            );
        }

        // Insert request
        $stmt = $this->db->prepare("
            INSERT INTO ride_requests
              (passenger_id, driver_id, carpool_id, pickup_location, dropoff_location, passenger_count, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'accepted', NOW())
        ");
        $stmt->execute([
            $userId,
            $carpool['driver_id'],
            $carpoolId,
            $carpool['pickup_location'],
            $carpool['dropoff_location'],
            $requestedSeats
        ]);

        // Update seats & credits
        $this->db->prepare("UPDATE carpools SET occupied_seats = occupied_seats + ? WHERE id = ?")
            ->execute([$requestedSeats, $carpoolId]);
        $this->db->prepare("UPDATE users SET credits = credits - ? WHERE id = ?")
            ->execute([$totalCost, $userId]);

        return $this->reloadCarpoolWithMessage(
            $response,
            $carpoolId,
            "Successfully joined. {$totalCost} credits deducted."
        );
    }

    // ... other methods (startCarpool, completeCarpool, createForm, storeCarpool) remain unchanged ...

    /**
     * Helper to reload a single carpool with a flash message
     */
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
            'carpool'     => $carpool,
            'preferences' => $preferences,
            'join_message' => $joinMessage,
        ]);
    }
}
