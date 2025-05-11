

namespace App\Controllers;

use App\Models\Ride;
use App\Models\RideRequest;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use PDO;

class RideController
{
    private $db;
    private $view;
    private Ride $rideModel;
    private RideRequest $requestModel;

    public function __construct(ContainerInterface $container)
    {
        $this->db = $container->get('db');
        $this->view = $container->get('view');
        $this->rideModel = new Ride($this->db);
        $this->requestModel = new RideRequest($this->db);
    }

    public function requestRide(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'user') {
            return $this->jsonResponse($response, ['error' => 'Unauthorized'], 403);
        }

        $passengerId = $_SESSION['user']['id'];
        $data = json_decode($request->getBody()->getContents(), true);

        if (!isset($data['pickup_location'], $data['dropoff_location'], $data['passenger_count'])) {
            return $this->jsonResponse($response, ['error' => 'Missing fields'], 400);
        }

        $passengerCount = (int) $data['passenger_count'];
        if ($passengerCount < 1 || $passengerCount > 4) {
            return $this->jsonResponse($response, ['error' => 'Passenger count must be between 1 and 4'], 400);
        }

        try {
            $this->requestModel->create([
                'passenger_id' => $passengerId,
                'pickup_location' => $data['pickup_location'],
                'dropoff_location' => $data['dropoff_location'],
                'passenger_count' => $passengerCount
            ]);

            return $this->jsonResponse($response, ['message' => 'Ride request submitted successfully'], 201);
        } catch (\PDOException $e) {
            return $this->jsonResponse($response, ['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }
    public function acceptRideRequest(Request $request, Response $response, array $args): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Vérifie que l'utilisateur est connecté en tant que conducteur
        // Check if user is a logged-in driver
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'driver') {
            return $this->jsonResponse($response, ['error' => 'Unauthorized access'], 403);
        }

        $driverId = $_SESSION['user']['id'];
        $rideRequestId = $args['id'];

        try {
            // Récupère les informations de la demande
            // Fetch ride request details
            $stmt = $this->db->prepare("SELECT * FROM ride_requests WHERE id = :id AND status = 'pending'");
            $stmt->execute(['id' => $rideRequestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                return $this->jsonResponse($response, ['error' => 'Ride request not found or already accepted'], 404);
            }

            // Vérifie les places disponibles du conducteur
            // Check driver's vehicle seat availability
            $vehicleStmt = $this->db->prepare("SELECT id, seats FROM vehicles WHERE driver_id = :driver_id");
            $vehicleStmt->execute(['driver_id' => $driverId]);
            $vehicle = $vehicleStmt->fetch(PDO::FETCH_ASSOC);

            if (!$vehicle || $vehicle['seats'] < $request['passenger_count']) {
                return $this->jsonResponse($response, ['error' => 'Not enough available seats'], 400);
            }

            $remainingSeats = $vehicle['seats'] - $request['passenger_count'];

            // Crée le trajet dans la table `rides`
            // Insert new ride
            $insertStmt = $this->db->prepare("
                INSERT INTO rides (passenger_id, driver_id, vehicle_id, pickup_location, dropoff_location, status, created_at)
                VALUES (:passenger_id, :driver_id, :vehicle_id, :pickup, :dropoff, 'accepted', NOW())
            ");
            $insertStmt->execute([
                'passenger_id' => $request['passenger_id'],
                'driver_id' => $driverId,
                'vehicle_id' => $vehicle['id'],
                'pickup' => $request['pickup_location'],
                'dropoff' => $request['dropoff_location']
            ]);

            // Met à jour le statut de la demande
            // Update ride request status
            $updateStmt = $this->db->prepare("UPDATE ride_requests SET status = 'accepted', driver_id = :driver_id WHERE id = :id");
            $updateStmt->execute([
                'driver_id' => $driverId,
                'id' => $rideRequestId
            ]);

            // Met à jour les places restantes
            // Update available seats in vehicle
            $seatUpdate = $this->db->prepare("UPDATE vehicles SET seats = :seats WHERE id = :id");
            $seatUpdate->execute([
                'seats' => $remainingSeats,
                'id' => $vehicle['id']
            ]);

            return $this->jsonResponse($response, ['message' => 'Ride accepted successfully'], 200);
        } catch (\PDOException $e) {
            error_log("DB ERROR: " . $e->getMessage());
            return $this->jsonResponse($response, ['error' => 'Database error'], 500);
        }
    }

        /**
     * Passager demande un trajet.
     * Passenger requests a ride.
     */
    public function requestRide(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);

        if (!isset($data['passenger_id'], $data['pickup_location'], $data['dropoff_location'])) {
            return $this->jsonResponse($response, ['error' => 'Champs manquants / Missing fields'], 400);
        }

        $stmt = $this->db->prepare("INSERT INTO rides (passenger_id, pickup_location, dropoff_location, status) 
                                    VALUES (:passenger_id, :pickup, :dropoff, 'pending')");
        $stmt->execute([
            'passenger_id' => $data['passenger_id'],
            'pickup' => $data['pickup_location'],
            'dropoff' => $data['dropoff_location']
        ]);

        return $this->jsonResponse($response, ['message' => 'Demande de trajet soumise / Ride request submitted successfully'], 201);
    }

    /**
     * Conducteur consulte les trajets ouverts.
     * Driver views open ride requests.
     */
    public function getOpenRides(Request $request, Response $response): Response
    {
        $stmt = $this->db->query("SELECT * FROM rides WHERE status = 'pending'");
        $rides = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->jsonResponse($response, ['rides' => $rides], 200);
    }

    /**
     * Historique des trajets du passager.
     *  Passenger ride history.
     */
    public function getPassengerRideHistory(Request $request, Response $response): Response 
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'user') {
            return $this->jsonResponse($response, ['error' => 'Non autorisé / Unauthorized'], 401);
        }

        $passenger_id = $_SESSION['user']['id'];

        $stmt = $this->db->prepare("SELECT * FROM rides WHERE passenger_id = :passenger_id ORDER BY created_at DESC");
        $stmt->execute(['passenger_id' => $passenger_id]);
        $rides = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view->render($response, 'ride-history.twig', ['rides' => $rides]);

    }

    /**
     * Le conducteur accepte un trajet.
     * Driver accepts a ride.
     */
    public function acceptRide(Request $request, Response $response, array $args): Response
    {
        $ride_id = $args['id'];
        $data = json_decode($request->getBody()->getContents(), true);

        if (!isset($data['driver_id'], $data['vehicle_id'])) {
            return $this->jsonResponse($response, ['error' => 'Champs manquants / Missing driver or vehicle ID'], 400);
        }

        // Vérifier si le trajet est encore disponible
        // Check if ride is still available
        $checkStmt = $this->db->prepare("SELECT status FROM rides WHERE id = :ride_id");
        $checkStmt->execute(['ride_id' => $ride_id]);
        $ride = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$ride || $ride['status'] !== 'pending') {
            return $this->jsonResponse($response, ['error' => 'Le trajet a déjà été accepté / Ride has already been accepted'], 400);
        }

        // Accepter le trajet
        // Accept the ride
        $stmt = $this->db->prepare("UPDATE rides SET driver_id = :driver_id, vehicle_id = :vehicle_id, status = 'accepted' 
                                    WHERE id = :ride_id AND status = 'pending'");
        $stmt->execute([
            'driver_id' => $data['driver_id'],
            'vehicle_id' => $data['vehicle_id'],
            'ride_id' => $ride_id
        ]);

        return $this->jsonResponse($response, ['message' => 'Trajet accepté avec succès / Ride accepted successfully'], 200);
    }

    /**
     *  Marquer un trajet comme terminé.
     * Mark ride as completed.
     */
    public function completeRide(Request $request, Response $response, array $args): Response
    {
        $ride_id = $args['id'];

        $stmt = $this->db->prepare("UPDATE rides SET status = 'completed' WHERE id = :ride_id AND status = 'accepted'");
        $stmt->execute(['ride_id' => $ride_id]);

        return $this->jsonResponse($response, ['message' => 'Trajet marqué comme terminé / Ride marked as completed'], 200);
    }

    /**
     * Annuler un trajet (par le passager avant acceptation).
     * Cancel a ride (by passenger before acceptance).
     */
    public function cancelRide(Request $request, Response $response, array $args): Response
    {
        $ride_id = $args['id'];

        $stmt = $this->db->prepare("UPDATE rides SET status = 'cancelled' WHERE id = :ride_id AND status = 'pending'");
        $stmt->execute(['ride_id' => $ride_id]);

        return $this->jsonResponse($response, ['message' => 'Trajet annulé / Ride cancelled'], 200);
    }

    /**
     *  Historique des trajets du conducteur.
     *  Driver ride history.
     */
    public function getDriverRideHistory(Request $request, Response $response): Response {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    
        //  Debugging: Log session data
        error_log("🔍 Checking Session for Driver: " . json_encode($_SESSION));
    
        // Ensure the user is authenticated and a driver
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'driver') {
            error_log("🚨 No valid driver session found.");
            return $this->jsonResponse($response, ['error' => 'Non autorisé / Unauthorized'], 401);
        }
    
        $driver_id = $_SESSION['user']['id'];
    
        //  Fetch driver's ride history
        $stmt = $this->db->prepare("SELECT * FROM rides WHERE driver_id = :driver_id AND status IN ('accepted', 'completed')");
        $stmt->execute(['driver_id' => $driver_id]);
        $rides = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        return $this->view->render($response, 'driver-ride-history.twig', ['rides' => $rides]);

    }
    

    /**
     * Fonction utilitaire pour renvoyer une réponse JSON.
     * Utility function to send a JSON response.
     */
    private function jsonResponse(Response $response, array $data, int $statusCode = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withStatus($statusCode);
    }
    
}


    private function jsonResponse(Response $response, array $data, int $statusCode = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withStatus($statusCode);
    }
}
