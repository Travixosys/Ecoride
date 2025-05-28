<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use PDO;
use PDOException;

class DriverController
{
    private $db;
    protected $view;
    private $container;

    public function __construct(ContainerInterface $container)
    {
        // Stocke les objets partagés comme la base de données et le moteur de vue
        // Store shared objects like DB and view engine
        $this->container = $container;
        $this->db = $container->get('db');
        $this->view = $container->get('view');
    }

    /**
     * Enregistrer un conducteur avec les détails du véhicule
     * Register a driver with vehicle details
     */
    public function registerDriver(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true) ?? [];

        // Champs utilisateur requis / Required user fields
        if (empty($data['email']) || empty($data['password']) || empty($data['name']) || empty($data['phone_number'])) {
            return $this->jsonResponse($response, ['error' => 'Champs obligatoires manquants / Missing required fields'], 400);
        }

        // Champs du véhicule requis / Required vehicle fields
        if (empty($data['make']) || empty($data['model']) || empty($data['year']) || empty($data['plate']) || empty($data['seats'])) {
            return $this->jsonResponse($response, ['error' => 'Détails du véhicule manquants / Missing vehicle details'], 400);
        }

        try {
            $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

            // Insertion dans la table users / Insert into users
            $stmt = $this->db->prepare("INSERT INTO users (name, email, password, role, phone_number) 
                                        VALUES (:name, :email, :password, 'driver', :phone_number)");
            $stmt->execute([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $hashedPassword,
                'phone_number' => $data['phone_number']
            ]);

            $driverId = $this->db->lastInsertId();

            // Insertion des détails du véhicule / Insert vehicle info
            $stmt = $this->db->prepare("INSERT INTO vehicles (driver_id, make, model, year, plate, seats) 
                                        VALUES (:driver_id, :make, :model, :year, :plate, :seats)");
            $stmt->execute([
                'driver_id' => $driverId,
                'make' => $data['make'],
                'model' => $data['model'],
                'year' => $data['year'],
                'plate' => $data['plate'],
                'seats' => $data['seats']
            ]);

            return $this->jsonResponse($response, ['message' => 'Conducteur enregistré avec succès / Driver registered successfully'], 201);
        } catch (PDOException $e) {
            return $this->jsonResponse($response, ['error' => 'Erreur BDD / DB Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Historique des trajets acceptés ou terminés du conducteur
     * Fetch driver's accepted or completed ride history
     */
    public function getRideHistory(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'driver') {
            return $this->jsonResponse($response, ['error' => 'Non autorisé / Unauthorized'], 401);
        }

        $driver_id = $_SESSION['user']['id'];

        // Fetch completed or accepted rides
        $stmt = $this->db->prepare("SELECT * FROM rides WHERE driver_id = :driver_id AND status IN ('accepted', 'completed')");
        $stmt->execute(['driver_id' => $driver_id]);
        $rides = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch all carpools for the driver
        $stmt = $this->db->prepare("SELECT * FROM carpools WHERE driver_id = :driver_id");
        $stmt->execute(['driver_id' => $driver_id]);
        $carpools = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Pass both to the template
        return $this->view->render($response, 'driver-dasboard.twig', [
            'rides' => $rides,
            'carpools' => $carpools
        ]);
    }

    /**
     * Accepter une demande de trajet si des places sont disponibles
     * Accept a ride request if seats are available
     */
    public function acceptRide(Request $request, Response $response, array $args)
    {
        $rideRequestId = $args['id'];

        $stmt = $this->db->prepare("SELECT * FROM ride_requests WHERE id = :id");
        $stmt->execute([':id' => $rideRequestId]);
        $rideRequest = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$rideRequest || $rideRequest['status'] !== 'pending') {
            return $this->jsonResponse($response, ['error' => 'Demande introuvable ou déjà traitée / Request not found or already processed'], 400);
        }

        $driverId = $_SESSION['user']['id'];

        $stmt = $this->db->prepare("SELECT * FROM carpools WHERE driver_id = :driver_id AND status = 'upcoming' ORDER BY id DESC LIMIT 1");
        $stmt->execute([':driver_id' => $driverId]);
        $carpool = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$carpool) {
            return $this->jsonResponse($response, ['error' => 'Aucun covoiturage actif / No active carpool'], 400);
        }

        $remainingSeats = $carpool['total_seats'] - $carpool['occupied_seats'];
        $passengerCount = $rideRequest['passenger_count'] ?? 1;

        if ($passengerCount > $remainingSeats) {
            return $this->jsonResponse($response, ['error' => 'Pas assez de places / Not enough available seats'], 400);
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE ride_requests SET status='accepted' WHERE id = :id");
            $stmt->execute([':id' => $rideRequestId]);

            $newOccupied = $carpool['occupied_seats'] + $passengerCount;
            $stmt = $this->db->prepare("UPDATE carpools SET occupied_seats = :occupied WHERE id = :id");
            $stmt->execute([
                ':occupied' => $newOccupied,
                ':id' => $carpool['id']
            ]);

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            return $this->jsonResponse($response, ['error' => 'Erreur BDD / DB error: ' . $e->getMessage()], 500);
        }

        return $this->jsonResponse($response, ['success' => 'Trajet accepté / Ride accepted successfully']);
    }

    /**
     * Créer un covoiturage à venir
     * Create a new upcoming carpool
     */
    public function createCarpool(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $driverId = $_SESSION['user']['id'];
        $vehicleId = $data['vehicle_id'] ?? null;
        $totalSeats = $data['total_seats'] ?? 4;

        $stmt = $this->db->prepare("INSERT INTO carpools (driver_id, vehicle_id, total_seats, occupied_seats, status)
                                     VALUES (:driver_id, :vehicle_id, :total_seats, 0, 'upcoming')");
        $stmt->execute([
            ':driver_id' => $driverId,
            ':vehicle_id' => $vehicleId,
            ':total_seats' => $totalSeats
        ]);

        return $this->jsonResponse($response, ['success' => true, 'message' => 'Covoiturage créé / Carpool created successfully']);
    }

    /**
     * Réponse JSON générique
     * Generic JSON response helper
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
