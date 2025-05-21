<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use MongoDB\Client as MongoDBClient;
use PDO;

class CarpoolController
{
    protected $view;
    protected $db;
    protected $mongo;

    public function __construct(ContainerInterface $container)
    {
        $this->view = $container->get('view');
        $this->db = $container->get('db');

        // ✅ Set up the MongoDB client
        $client = new MongoDBClient('mongodb://localhost:27017'); 
        $this->mongo = $client->ecoridepool->user_preferences;
    }

    /**
     * Display all available carpools
     * Affiche tous les trajets disponibles
     */
    public function listAvailable(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();

        $pickup = $params['pickup'] ?? null;
        $dropoff = $params['dropoff'] ?? null;
        $minSeats = $params['min_seats'] ?? null;

        $sql = "
        SELECT c.*, u.name AS driver_name, v.energy_type
        FROM carpools c
        JOIN users u ON c.driver_id = u.id
        JOIN vehicles v ON c.vehicle_id = v.id
        WHERE c.status = 'upcoming'
          AND (c.total_seats - c.occupied_seats) > 0
    ";

        $conditions = [];
        $values = [];
        $energy = $params['energy'] ?? null;

        if ($pickup) {
            $conditions[] = 'c.pickup_location LIKE ?';
            $values[] = "%$pickup%";
        }

        if ($dropoff) {
            $conditions[] = 'c.dropoff_location LIKE ?';
            $values[] = "%$dropoff%";
        }

        if ($minSeats) {
            $conditions[] = '(c.total_seats - c.occupied_seats) >= ?';
            $values[] = (int)$minSeats;
        }


        if ($energy) {
            $conditions[] = 'v.energy_type = ?';
            $values[] = $energy;
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
            'filters' => [
                'pickup' => $pickup,
                'dropoff' => $dropoff,
                'min_seats' => $minSeats
            ]
        ]);
    }

 public function viewDetail(Request $request, Response $response, array $args): Response
    {
        $carpoolId = $args['id'];

        // Fetch SQL carpool + driver + vehicle info
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

        // ✅ Mongo fetch
        $driverId = (int) $carpool['driver_id'];
        $mongoResult = $this->mongo->findOne(['user_id' => $driverId]);

        $preferences = null;
        if ($mongoResult && isset($mongoResult['preferences'])) {
            $preferences = json_decode(json_encode($mongoResult['preferences']), true);
        }

        return $this->view->render($response, 'carpool-detail.twig', [
            'carpool' => $carpool,
            'preferences' => $preferences
        ]);
    }

    public function joinCarpool(Request $request, Response $response, array $args): Response
{
    $carpoolId = (int) $args['id'];
    $userId = $_SESSION['user']['id'] ?? null;
    $data = $request->getParsedBody();
    $requestedSeats = max(1, (int) $data['passenger_count']);

    // Fetch carpool to verify availability
    $stmt = $this->db->prepare("SELECT * FROM carpools WHERE id = ?");
    $stmt->execute([$carpoolId]);
    $carpool = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$carpool) {
        $response->getBody()->write("Carpool not found.");
        return $response->withStatus(404);
    }

    $availableSeats = $carpool['total_seats'] - $carpool['occupied_seats'];
    if ($requestedSeats > $availableSeats) {
        return $this->view->render($response, 'carpool-detail.twig', [
            'carpool' => $carpool,
            'join_message' => "Not enough available seats."
        ]);
    }

    // Check if already joined
    $stmt = $this->db->prepare("SELECT id FROM ride_requests WHERE passenger_id = ? AND carpool_id = ?");
    $stmt->execute([$userId, $carpoolId]);
    if ($stmt->fetch()) {
        return $this->view->render($response, 'carpool-detail.twig', [
            'carpool' => $carpool,
            'join_message' => "You already joined this carpool."
        ]);
    }

    // Insert ride request
   $stmt = $this->db->prepare("
    INSERT INTO ride_requests (passenger_id, driver_id, carpool_id, pickup_location, dropoff_location, passenger_count, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, 'accepted', NOW())
");
$stmt->execute([
    $userId,
    $carpool['driver_id'],
    $carpool['id'],
    $carpool['pickup_location'],
    $carpool['dropoff_location'],
    $requestedSeats
]);


    // Update occupied seats
    $stmt = $this->db->prepare("UPDATE carpools SET occupied_seats = occupied_seats + ? WHERE id = ?");
    $stmt->execute([$requestedSeats, $carpoolId]);

    // Reload detail with success message
    $carpool['occupied_seats'] += $requestedSeats;
    return $this->view->render($response, 'carpool-detail.twig', [
        'carpool' => $carpool,
        'join_message' => "You have successfully joined the carpool!"
    ]);
}


    /**
     * Show form to offer a new carpool (driver)
     * Affiche le formulaire pour proposer un nouveau covoiturage (conducteur)
     */
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

    /**
     * Save a new carpool offered by the driver
     * Enregistre un nouveau covoiturage proposé par le conducteur
     */
    public function storeCarpool(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
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

        return $response
            ->withHeader('Location', '/carpools')
            ->withStatus(302);
    }
}
