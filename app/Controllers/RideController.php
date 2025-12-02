<?php

namespace App\Controllers;

use App\Models\Ride;
use App\Models\RideRequest;
use App\Models\Carpool;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use PDO;

/**
 * RideController
 * FR : Gère les opérations côté passager & conducteur (historique, acceptation, annulation…)
 * EN : Handles passenger & driver ride operations (history, accept, cancel…)
 */
class RideController
{
    /** @var PDO Connexion base MySQL • MySQL DB connection */
    private $db;
    /** @var \Slim\Views\Twig Vue Twig • Twig view engine */
    private $view;
    /** @var Ride        Modèle Ride • Ride model */
    private Ride $rideModel;
    /** @var RideRequest Modèle RideRequest • RideRequest model */
    private RideRequest $requestModel;
    /** @var Carpool     Modèle Carpool • Carpool model */
    private Carpool $carpoolModel;

    /*--------------------------------------------------------------------
      __construct()
      FR : Récupère DB, vue et instancie les modèles
      EN : Pull DB, view and instantiate models
    --------------------------------------------------------------------*/
    public function __construct(ContainerInterface $container)
    {
        $this->db    = $container->get('db');
        $this->view  = $container->get('view');
        $this->rideModel    = new Ride($this->db);
        $this->requestModel = new RideRequest($this->db);
        $this->carpoolModel = new Carpool($this->db);
    }

    /*--------------------------------------------------------------------
      getPassengerRideHistory()
      FR : Historique trajets (passager) groupé par statut covoiturage
      EN : Passenger ride history grouped by carpool status
    --------------------------------------------------------------------*/
    public function getPassengerRideHistory(Request $request, Response $response): Response
    {
        $userId = $_SESSION['user']['id'];

        // Requête : récupère les demandes + infos covoiturage + flag review
        $stmt = $this->db->prepare("
            SELECT rr.*, c.departure_time, c.pickup_location, c.dropoff_location,
                   c.status AS carpool_status,
                   EXISTS (
                       SELECT 1 FROM ride_reviews r
                       WHERE r.ride_request_id = rr.id
                         AND r.reviewer_id     = :user_id
                   ) AS review_exists
            FROM ride_requests rr
            JOIN carpools c ON rr.carpool_id = c.id
            WHERE rr.passenger_id = :user_id
            ORDER BY c.departure_time DESC
        ");
        $stmt->execute(['user_id' => $userId]);
        $rides = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Initialise groupes par statut
        $grouped = [
            'upcoming'    => [],
            'in progress' => [],
            'completed'   => [],
            'canceled'    => [],
        ];
        foreach ($rides as $ride) {
            $status = $ride['carpool_status'];
            if (isset($grouped[$status])) $grouped[$status][] = $ride;
        }

        return $this->view->render($response, 'rides.twig', [
            'grouped_rides' => $grouped
        ]);
    }

    /*--------------------------------------------------------------------
      getDriverRideHistory()
      FR : Historique conducteur : covoiturages + avis reçus
      EN : Driver ride history: carpools + received reviews
    --------------------------------------------------------------------*/
    public function getDriverRideHistory(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'driver') {
            return $this->jsonResponse($response, ['error' => 'Unauthorized'], 401);
        }
        $driverId = $_SESSION['user']['id'];

        try {
            /* 1. Covoiturages créés (tri pers. : en cours, avec passagers, etc.) */
            $carpoolsStmt = $this->db->prepare("
                SELECT id, pickup_location, dropoff_location, departure_time,
                       total_seats, occupied_seats, status
                FROM carpools
                WHERE driver_id = :driver_id
                  AND status IN ('upcoming','in progress','completed')
                ORDER BY
                  CASE
                    WHEN status = 'in progress'                         THEN 1
                    WHEN status = 'upcoming' AND occupied_seats > 0     THEN 2
                    WHEN status = 'upcoming' AND occupied_seats = 0     THEN 3
                    WHEN status = 'completed'                           THEN 4
                    ELSE 5
                  END,
                  departure_time
            ");
            $carpoolsStmt->execute(['driver_id' => $driverId]);
            $carpools = $carpoolsStmt->fetchAll(PDO::FETCH_ASSOC);

            /* 2. Avis approuvés reçus */
            $reviewsStmt = $this->db->prepare("
                SELECT rr.rating, rr.comment, rr.created_at, u.name AS reviewer_name
                FROM ride_reviews rr
                JOIN users u ON rr.reviewer_id = u.id
                WHERE rr.target_id = :driver_id AND rr.status = 'approved'
                ORDER BY rr.created_at DESC
            ");
            $reviewsStmt->execute(['driver_id' => $driverId]);
            $reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->view->render($response, 'driver-dashboard.twig', [
                'carpools' => $carpools,
                'reviews'  => $reviews
            ]);
        } catch (\PDOException $e) {
            return $this->jsonResponse(
                $response,
                ['error' => 'Database error: ' . $e->getMessage()],
                500
            );
        }
    }

    /*--------------------------------------------------------------------
      acceptRide()
      FR : Le conducteur accepte une demande et réserve les sièges
      EN : Driver accepts ride request, reserves seats
    --------------------------------------------------------------------*/
    public function acceptRide(Request $request, Response $response, array $args): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'driver') {
            return $this->jsonResponse($response, ['error' => 'Unauthorized'], 403);
        }

        $driverId      = $_SESSION['user']['id'];
        $rideRequestId = $args['id'] ?? null;
        if (!$rideRequestId) {
            return $this->jsonResponse($response, ['error' => 'Missing ride request ID'], 400);
        }

        try {
            $this->db->beginTransaction();

            /* A. Charge la demande ‘pending’ */
            $stmt = $this->db->prepare("SELECT * FROM ride_requests WHERE id = :id AND status = 'pending'");
            $stmt->execute(['id' => $rideRequestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$request) {
                $this->db->rollBack();
                return $this->jsonResponse(
                    $response,
                    ['error' => 'Ride request not found or already accepted'],
                    404
                );
            }

            /* B. Véhicule du conducteur */
            $vehicleStmt = $this->db->prepare("SELECT id, seats FROM vehicles WHERE driver_id = :driver_id");
            $vehicleStmt->execute(['driver_id' => $driverId]);
            $vehicle = $vehicleStmt->fetch(PDO::FETCH_ASSOC);
            if (!$vehicle) {
                $this->db->rollBack();
                return $this->jsonResponse(
                    $response,
                    ['error' => 'No registered vehicle found for this driver'],
                    400
                );
            }

            $requestedSeats = (int)$request['passenger_count'];

            /* C. Covoiturage ‘upcoming’ sinon création */
            $carpoolStmt = $this->db->prepare("
                SELECT * FROM carpools
                WHERE driver_id = :driver_id AND status = 'upcoming'
                ORDER BY id DESC LIMIT 1
            ");
            $carpoolStmt->execute(['driver_id' => $driverId]);
            $carpool = $carpoolStmt->fetch(PDO::FETCH_ASSOC);

            if (!$carpool) {
                $create = $this->db->prepare("
                    INSERT INTO carpools
                        (driver_id, vehicle_id, total_seats, occupied_seats, status, created_at, updated_at)
                    VALUES (:driver, :vehicle, :seats, 0, 'upcoming', NOW(), NOW())
                ");
                $create->execute([
                    'driver'  => $driverId,
                    'vehicle' => $vehicle['id'],
                    'seats'   => $vehicle['seats']
                ]);
                $carpool = [
                    'id'             => $this->db->lastInsertId(),
                    'total_seats'    => $vehicle['seats'],
                    'occupied_seats' => 0
                ];
            }

            /* D. Vérifie disponibilité sièges */
            $available = $carpool['total_seats'] - $carpool['occupied_seats'];
            if ($requestedSeats > $available) {
                $this->db->rollBack();
                return $this->jsonResponse(
                    $response,
                    ['error' => 'Not enough seats available'],
                    400
                );
            }

            /* E. Insère ride + met à jour tables */
            $this->db->prepare("
                INSERT INTO rides (
                    passenger_id, driver_id, vehicle_id,
                    pickup_location, dropoff_location,
                    status, ride_request_id
                ) VALUES (
                    :passenger, :driver, :vehicle,
                    :pickup, :dropoff, 'accepted', :req_id
                )
            ")->execute([
                'passenger' => $request['passenger_id'],
                'driver'    => $driverId,
                'vehicle'   => $vehicle['id'],
                'pickup'    => $request['pickup_location'],
                'dropoff'   => $request['dropoff_location'],
                'req_id'    => $rideRequestId
            ]);

            $this->db->prepare("
                UPDATE ride_requests
                   SET status='accepted', driver_id=:driver
                 WHERE id=:id
            ")->execute(['driver' => $driverId, 'id' => $rideRequestId]);

            $this->db->prepare("
                UPDATE carpools
                   SET occupied_seats = occupied_seats + :taken,
                       updated_at     = NOW()
                 WHERE id = :cid
            ")->execute(['taken' => $requestedSeats, 'cid' => $carpool['id']]);

            $this->db->commit();
            return $this->jsonResponse(
                $response,
                ['message' => 'Ride accepted successfully']
            );
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("acceptRide error: " . $e->getMessage());
            return $this->jsonResponse(
                $response,
                ['error' => 'An error occurred while accepting the ride'],
                500
            );
        }
    }

    /*--------------------------------------------------------------------
      completeRide()
      FR : Marque la course terminée et libère les sièges dans le carpool
      EN : Mark ride completed & release seats in carpool
    --------------------------------------------------------------------*/
    public function completeRide(Request $request, Response $response, array $args): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'driver') {
            return $this->jsonResponse($response, ['error' => 'Unauthorized'], 403);
        }

        $rideId = $args['id'];
        $driverId = $_SESSION['user']['id'];

        try {
            $this->db->beginTransaction();

            // Détails ride + passenger_count via jointure
            // Verify driver ownership
            $stmt = $this->db->prepare("
                SELECT r.*, rr.passenger_count
                FROM rides r
                JOIN ride_requests rr ON r.ride_request_id = rr.id
                WHERE r.id = :ride_id AND r.driver_id = :driver_id
            ");
            $stmt->execute(['ride_id' => $rideId, 'driver_id' => $driverId]);
            $ride = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ride) {
                $this->db->rollBack();
                return $this->jsonResponse($response, ['error' => 'Ride not found or not authorized'], 404);
            }

            /* 1) Statut completed */
            $this->rideModel->updateStatus($rideId, 'completed');

            /* 2) Carpool actif du driver */
            $carpoolId = $this->carpoolModel->getActiveCarpoolId($ride['driver_id']);
            if ($carpoolId !== null) {
                /* 3) Libère sièges */
                $this->carpoolModel->decrementOccupiedSeats($carpoolId, (int)$ride['passenger_count']);
            }

            $this->db->commit();
            return $this->jsonResponse($response, ['message' => 'Ride completed successfully']);
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("completeRide error: " . $e->getMessage());
            return $this->jsonResponse(
                $response,
                ['error' => 'An error occurred while completing the ride'],
                500
            );
        }
    }

    /*--------------------------------------------------------------------
      cancelRide()
      FR : Passager annule sa demande → rembourse crédits
      EN : Passenger cancels request → refund credits
    --------------------------------------------------------------------*/
    public function cancelRide(Request $request, Response $response, array $args): Response
    {
        $rideId = (int)$args['id'];
        $userId = $_SESSION['user']['id'];

        // 1. Récupère demande
        $stmt = $this->db->prepare("
            SELECT * FROM ride_requests
            WHERE id = ? AND passenger_id = ?
        ");
        $stmt->execute([$rideId, $userId]);
        $ride = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ride || $ride['status'] !== 'pending') {
            // Simple redirection si déjà traité
            return $response->withHeader('Location', '/rides')->withStatus(302);
        }

        // 2. Annule (use 'canceled' for consistency across codebase)
        $this->db->prepare("UPDATE ride_requests SET status='canceled' WHERE id=?")
            ->execute([$rideId]);

        // 3. Rembourse (passenger_count * 5)
        $refund = $ride['passenger_count'] * 5;
        $this->db->prepare("UPDATE users SET credits = credits + ? WHERE id = ?")
            ->execute([$refund, $userId]);

        // 4. Libère sièges s’il y avait carpool
        if (!empty($ride['carpool_id'])) {
            $this->db->prepare("
                UPDATE carpools
                   SET occupied_seats = occupied_seats - ?
                 WHERE id = ?
            ")->execute([$ride['passenger_count'], $ride['carpool_id']]);
        }

        return $response->withHeader('Location', '/rides')->withStatus(302);
    }

    /*--------------------------------------------------------------------
      listAvailableCarpools()
      FR : Liste des covoiturages à venir (places dispo)
      EN : List upcoming carpools with seats available
    --------------------------------------------------------------------*/
    public function listAvailableCarpools(Request $request, Response $response): Response
    {
        $stmt = $this->db->prepare("
            SELECT c.*, u.name AS driver_name, v.energy_type
            FROM carpools c
            JOIN users    u ON c.driver_id  = u.id
            JOIN vehicles v ON c.vehicle_id = v.id
            WHERE c.status = 'upcoming'
              AND (c.total_seats - c.occupied_seats) > 0
            ORDER BY c.created_at DESC
        ");
        $stmt->execute();
        $carpools = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view->render($response, 'carpool-list.twig', [
            'carpools' => $carpools
        ]);
    }

    /*--------------------------------------------------------------------
      jsonResponse()
      FR : Helper JSON générique
      EN : Generic JSON response helper
    --------------------------------------------------------------------*/
    private function jsonResponse(Response $response, array $data, int $statusCode = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withStatus($statusCode);
    }
}
