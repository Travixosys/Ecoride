<?php

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
            // Get the pending ride request
            $stmt = $this->db->prepare("SELECT * FROM ride_requests WHERE id = :id AND status = 'pending'");
            $stmt->execute(['id' => $rideRequestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                return $this->jsonResponse($response, ['error' => 'Ride request not found or already accepted'], 404);
            }

            // Get vehicle info of the driver
            $vehicleStmt = $this->db->prepare("SELECT id, seats FROM vehicles WHERE driver_id = :driver_id");
            $vehicleStmt->execute(['driver_id' => $driverId]);
            $vehicle = $vehicleStmt->fetch(PDO::FETCH_ASSOC);

            if (!$vehicle || $vehicle['seats'] < $request['passenger_count']) {
                return $this->jsonResponse($response, ['error' => 'Not enough seats'], 400);
            }

            // Insert into rides table
            $insertStmt = $this->db->prepare("INSERT INTO rides (passenger_id, driver_id, vehicle_id, pickup_location, dropoff_location, status) VALUES (:passenger_id, :driver_id, :vehicle_id, :pickup, :dropoff, 'accepted')");
            $insertStmt->execute([
                'passenger_id' => $request['passenger_id'],
                'driver_id' => $driverId,
                'vehicle_id' => $vehicle['id'],
                'pickup' => $request['pickup_location'],
                'dropoff' => $request['dropoff_location']
            ]);

            // Update ride request status
            $updateStmt = $this->db->prepare("UPDATE ride_requests SET status = 'accepted', driver_id = :driver_id WHERE id = :id");
            $updateStmt->execute(['driver_id' => $driverId, 'id' => $rideRequestId]);

            // Update remaining seats
            $remainingSeats = $vehicle['seats'] - $request['passenger_count'];
            $seatUpdate = $this->db->prepare("UPDATE vehicles SET seats = :seats WHERE id = :id");
            $seatUpdate->execute(['seats' => $remainingSeats, 'id' => $vehicle['id']]);

            return $this->jsonResponse($response, ['message' => 'Ride accepted successfully'], 200);
        } catch (\PDOException $e) {
            return $this->jsonResponse($response, ['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }

    public function completeRide(Request $request, Response $response, array $args): Response
    {
        $rideId = $args['id'];
        $this->rideModel->updateStatus($rideId, 'completed');
        return $this->jsonResponse($response, ['message' => 'Ride completed successfully']);
    }

    public function cancelRide(Request $request, Response $response, array $args): Response
    {
        $rideId = $args['id'];
        $this->rideModel->updateStatus($rideId, 'cancelled');
        return $this->jsonResponse($response, ['message' => 'Ride cancelled']);
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
