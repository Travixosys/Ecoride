<?php

namespace App\Controllers;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class AdminController
{
    protected $container;
    protected $db;
    protected $view;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->db = $container->get('db');
        $this->view = $container->get('view');
    }

    /**
     * ✅ Display the Admin Dashboard (Users & Rides)
     */
    public function dashboard(Request $request, Response $response): Response
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            return $this->jsonResponse($response, ['error' => 'Non autorisé / Unauthorized'], 403);
        }

        // Fetch users
        $stmt = $this->db->query("SELECT id, name, email, role, phone_number, COALESCE(license_number, '') AS license_number FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);


        // Fetch all ride requests
        $stmt = $this->db->query("
        SELECT r.id, r.passenger_id, r.driver_id, r.pickup_location, r.dropoff_location, 
               r.status, r.created_at, 
               p.name as passenger_name, d.name as driver_name
        FROM ride_requests r
        LEFT JOIN users p ON r.passenger_id = p.id
        LEFT JOIN users d ON r.driver_id = d.id
        ORDER BY r.created_at DESC
    ");
        $rideRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch all accepted/completed rides
        $rideStmt = $this->db->query("
        SELECT r.id, r.passenger_id, r.driver_id, r.pickup_location, r.dropoff_location, 
               r.status, r.created_at, 
               p.name as passenger_name, d.name as driver_name
        FROM rides r
        LEFT JOIN users p ON r.passenger_id = p.id
        LEFT JOIN users d ON r.driver_id = d.id
        ORDER BY r.created_at DESC
    ");
        $rides = $rideStmt->fetchAll(PDO::FETCH_ASSOC);

        $allRides = array_merge($rideRequests, $rides);
        return $this->view->render($response, 'admin.twig', [
            'users' => $users,
            'rides' => $allRides
        ]);
    }


    /**
     * ✅ Update User Role & License
     */
    public function updateUser(Request $request, Response $response, array $args): Response
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            return $this->jsonResponse($response, ['error' => 'Non autorisé / Unauthorized'], 403);
        }

        $data = json_decode($request->getBody()->getContents(), true);
        $userId = $args['id'];

        if ($data['role'] === 'driver' && empty($data['license_number'])) {
            return $this->jsonResponse($response, ['error' => 'License number is required for drivers'], 400);
        }

        $stmt = $this->db->prepare("UPDATE users SET role = :role, license_number = :license WHERE id = :id");
        $stmt->execute([
            'role' => $data['role'],
            'license' => $data['license_number'] ?? null,
            'id' => $userId
        ]);

        return $this->jsonResponse($response, ['message' => 'Utilisateur mis à jour avec succès / User updated successfully']);
    }

    /**
     * ✅ Delete a User (with Data Integrity)
     */
    public function deleteUser(Request $request, Response $response, array $args): Response
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            return $this->jsonResponse($response, ['error' => 'Non autorisé / Unauthorized'], 403);
        }

        $userId = $args['id'];

        // Begin transaction for data integrity
        $this->db->beginTransaction();

        try {
            // Cancel all rides linked to this user
            $updateRidesStmt = $this->db->prepare("
                UPDATE rides SET status = 'cancelled' 
                WHERE (passenger_id = :user_id OR driver_id = :user_id) 
                AND status NOT IN ('completed', 'cancelled')
            ");
            $updateRidesStmt->execute(['user_id' => $userId]);

            // Delete the user
            $deleteStmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
            $deleteStmt->execute(['id' => $userId]);

            $this->db->commit();

            return $this->jsonResponse($response, ['message' => 'Utilisateur supprimé avec succès / User deleted successfully']);
        } catch (\Exception $e) {
            $this->db->rollBack();
            return $this->jsonResponse($response, ['error' => 'Erreur lors de la suppression de l\'utilisateur'], 500);
        }
    }

    /**
     * ✅ Delete a Ride (with Validation)
     */
    public function deleteRide(Request $request, Response $response, array $args): Response
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            return $this->jsonResponse($response, ['error' => 'Non autorisé / Unauthorized'], 403);
        }

        $rideId = $args['id'];

        // Delete the ride
        $deleteStmt = $this->db->prepare("DELETE FROM rides WHERE id = :id");
        $deleteStmt->execute(['id' => $rideId]);

        return $this->jsonResponse($response, ['message' => 'Trajet supprimé avec succès / Ride deleted successfully']);
    }

    /**
     * ✅ Utility Function for JSON Responses
     */
    private function jsonResponse(Response $response, array $data, int $statusCode = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($statusCode);
    }
}
