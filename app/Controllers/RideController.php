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

    private function jsonResponse(Response $response, array $data, int $statusCode = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withStatus($statusCode);
    }
}
