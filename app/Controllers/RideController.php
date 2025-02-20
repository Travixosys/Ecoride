<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;
use Psr\Container\ContainerInterface;

class RideController
{
    private $db;
    private $view;

    /**
     * Constructeur: Initialise la connexion à la base de données et la vue.
     * Constructor: Initializes database connection and view.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->db = $container->get('db');
        $this->view = $container->get('view'); 
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
