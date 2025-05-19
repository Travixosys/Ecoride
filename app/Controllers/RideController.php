<?php

namespace App\Controllers;

use App\Models\Ride;
use App\Models\RideRequest;
use App\Models\Carpool;
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
    private Carpool $carpoolModel;

    public function __construct(ContainerInterface $container)
    {
        $this->db = $container->get('db');
        $this->view = $container->get('view');
        $this->rideModel = new Ride($this->db);
        $this->requestModel = new RideRequest($this->db);
        $this->carpoolModel = new Carpool($this->db); // ✅ ADD THIS LINE

    }



    public function requestRide(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

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

    public function getPassengerRideHistory(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'user') {
            return $this->jsonResponse($response, ['error' => 'Non autorisé / Unauthorized'], 401);
        }

        $rides = $this->rideModel->findByPassengerId($_SESSION['user']['id']);
        return $this->view->render($response, 'ride-history.twig', ['rides' => $rides]);
    }

    public function getDriverRideHistory(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'driver') {
            return $this->jsonResponse($response, ['error' => 'Unauthorized'], 401);
        }

        $driverId = $_SESSION['user']['id'];

        try {
            $stmt = $this->db->prepare("SELECT * FROM rides WHERE driver_id = :driver_id AND status IN ('accepted', 'completed') ORDER BY created_at DESC");
            $stmt->execute(['driver_id' => $driverId]);
            $rides = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->view->render($response, 'driver-ride-history.twig', [
                'rides' => $rides
            ]);
        } catch (\PDOException $e) {
            return $this->jsonResponse($response, ['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }

    public function acceptRide(Request $request, Response $response, array $args): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'driver') {
            return $this->jsonResponse($response, ['error' => 'Unauthorized'], 403);
        }

        $driverId = $_SESSION['user']['id'];
        $rideRequestId = $args['id'] ?? null;

        if (!$rideRequestId) {
            return $this->jsonResponse($response, ['error' => 'Missing ride request ID'], 400);
        }

        try {
            $this->db->beginTransaction();

            // Fetch the ride request
            $stmt = $this->db->prepare("SELECT * FROM ride_requests WHERE id = :id AND status = 'pending'");
            $stmt->execute(['id' => $rideRequestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                $this->db->rollBack();
                return $this->jsonResponse($response, ['error' => 'Ride request not found or already accepted'], 404);
            }

            // Get vehicle details
            $vehicleStmt = $this->db->prepare("SELECT id, seats FROM vehicles WHERE driver_id = :driver_id");
            $vehicleStmt->execute(['driver_id' => $driverId]);
            $vehicle = $vehicleStmt->fetch(PDO::FETCH_ASSOC);

            if (!$vehicle) {
                $this->db->rollBack();
                return $this->jsonResponse($response, ['error' => 'No registered vehicle found for this driver'], 400);
            }

            $requestedSeats = (int)$request['passenger_count'];

            // Check or create an active carpool
            $carpoolStmt = $this->db->prepare("SELECT * FROM carpools WHERE driver_id = :driver_id AND status = 'upcoming'");
            $carpoolStmt->execute(['driver_id' => $driverId]);
            $carpool = $carpoolStmt->fetch(PDO::FETCH_ASSOC);

            if (!$carpool) {
                // Create new carpool if none exists
                $createCarpool = $this->db->prepare("INSERT INTO carpools (driver_id, vehicle_id, total_seats, occupied_seats, status, created_at, updated_at) VALUES (:driver_id, :vehicle_id, :total_seats, 0, 'upcoming', NOW(), NOW())");
                $createCarpool->execute([
                    'driver_id' => $driverId,
                    'vehicle_id' => $vehicle['id'],
                    'total_seats' => $vehicle['seats']
                ]);

                $carpoolId = $this->db->lastInsertId();
                $carpool = [
                    'id' => $carpoolId,
                    'occupied_seats' => 0,
                    'total_seats' => $vehicle['seats']
                ];
            }

            // Check if seats are available
            $availableSeats = (int)$carpool['total_seats'] - (int)$carpool['occupied_seats'];

            if ($requestedSeats > $availableSeats) {
                $this->db->rollBack();
                return $this->jsonResponse($response, ['error' => 'Not enough seats available'], 400);
            }

            // Accept the ride request
            $insertRide = $this->db->prepare("
                INSERT INTO rides (
                    passenger_id, driver_id, vehicle_id, pickup_location, dropoff_location, status, ride_request_id
                ) VALUES (
                    :passenger_id, :driver_id, :vehicle_id, :pickup, :dropoff, 'accepted', :ride_request_id
                )
           ");
            $insertRide->execute([
                'passenger_id' => $request['passenger_id'],
                'driver_id' => $driverId,
                'vehicle_id' => $vehicle['id'],
                'pickup' => $request['pickup_location'],
                'dropoff' => $request['dropoff_location'],
                'ride_request_id' => $rideRequestId  // ✅ now linking to the right request
            ]);


            $updateRequest = $this->db->prepare("UPDATE ride_requests SET status = 'accepted', driver_id = :driver_id WHERE id = :id");
            $updateRequest->execute(['driver_id' => $driverId, 'id' => $rideRequestId]);

            $updateCarpool = $this->db->prepare("UPDATE carpools SET occupied_seats = occupied_seats + :taken, updated_at = NOW() WHERE id = :carpool_id");
            $updateCarpool->execute(['taken' => $requestedSeats, 'carpool_id' => $carpool['id']]);

            $this->db->commit();

            return $this->jsonResponse($response, ['message' => 'Ride accepted successfully'], 200);
        } catch (\PDOException $e) {
            $this->db->rollBack();
            return $this->jsonResponse($response, [
                'error' => 'Database error',
                'details' => $e->getMessage()
            ], 500);
        }
    }


    public function completeRide(Request $request, Response $response, array $args): Response
    {
        $rideId = $args['id'];

        try {
            $this->db->beginTransaction();

            // Get the ride details before updating status
            // This join is critical - the ride_requests table holds the passenger_count
            $stmt = $this->db->prepare("
                SELECT r.*, rr.passenger_count 
                FROM rides r
                JOIN ride_requests rr ON r.passenger_id = rr.passenger_id
                WHERE r.id = :ride_id
            ");
            $stmt->execute(['ride_id' => $rideId]);
            $ride = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ride) {
                $this->db->rollBack();
                return $this->jsonResponse($response, ['error' => 'Ride not found'], 404);
            }

            // Step 1: Mark the ride as completed
            $this->rideModel->updateStatus($rideId, 'completed');

            // Step 2: Get carpool ID
            $carpoolId = $this->carpoolModel->getActiveCarpoolId($ride['driver_id']);

            if ($carpoolId !== null) {
                // Step 3: Decrement the seats by the passenger count
                $this->carpoolModel->decrementOccupiedSeats($carpoolId, (int)$ride['passenger_count']);
            }

            $this->db->commit();
            return $this->jsonResponse($response, ['message' => 'Ride completed successfully']);
        } catch (\PDOException $e) {
            $this->db->rollBack();
            return $this->jsonResponse($response, [
                'error' => 'Database error',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function cancelRide(Request $request, Response $response, array $args): Response
    {
        $rideId = $args['id'];

        try {
            $this->db->beginTransaction();

            // Get the ride details before updating status
            // This join is critical - the ride_requests table holds the passenger_count
            $stmt = $this->db->prepare("
                SELECT r.*, rr.passenger_count 
                FROM rides r
                JOIN ride_requests rr ON r.passenger_id = rr.passenger_id
                WHERE r.id = :ride_id
            ");
            $stmt->execute(['ride_id' => $rideId]);
            $ride = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ride) {
                $this->db->rollBack();
                return $this->jsonResponse($response, ['error' => 'Ride not found'], 404);
            }

            // Step 1: Mark the ride as cancelled
            $this->rideModel->updateStatus($rideId, 'cancelled');

            // Step 2: Get carpool ID
            $carpoolId = $this->carpoolModel->getActiveCarpoolId($ride['driver_id']);

            if ($carpoolId !== null) {
                // Step 3: Decrement the seats by the passenger count
                $this->carpoolModel->decrementOccupiedSeats($carpoolId, (int)$ride['passenger_count']);
            }

            $this->db->commit();
            return $this->jsonResponse($response, ['message' => 'Ride cancelled successfully']);
        } catch (\PDOException $e) {
            $this->db->rollBack();
            return $this->jsonResponse($response, [
                'error' => 'Database error',
                'details' => $e->getMessage()
            ], 500);
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
