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
     * Display the Admin Dashboard with Users and Ride Requests
     */
    /**
     * Display the Admin Dashboard with Users, Ride Requests, and Total Credits
     */
    public function dashboard(Request $request, Response $response): Response
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            return $this->jsonResponse($response, ['error' => 'Non autorisé / Unauthorized'], 403);
        }

        // Fetch all users
        $stmt = $this->db->query("
        SELECT id, name, email, role, phone_number, 
               COALESCE(license_number, '') AS license_number,
               COALESCE(suspended, 0) AS suspended
        FROM users
    ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        //  Fetch all ride requests
        $stmt = $this->db->query("
        SELECT r.id, r.passenger_id, r.driver_id, 
               r.pickup_location, r.dropoff_location, 
               r.status, r.created_at, 
               p.name AS passenger_name, 
               d.name AS driver_name
        FROM ride_requests r
        LEFT JOIN users p ON r.passenger_id = p.id
        LEFT JOIN users d ON r.driver_id = d.id
        ORDER BY r.created_at DESC
    ");
        $rideRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch total platform credits (2 credits per completed ride)
        $totalQuery = $this->db->query("
        SELECT COUNT(*) * 2 AS total_credits
        FROM ride_requests
        WHERE status = 'completed'
    ");
        $total = $totalQuery->fetch(PDO::FETCH_ASSOC);

        // Render the admin dashboard with all required data
        return $this->view->render($response, 'admin.twig', [
            'users' => $users,
            'rides' => $rideRequests,
            'total_credits' => $total['total_credits'] ?? 0
        ]);
    }

    /**
     * Update User Role and License Number
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

        return $this->jsonResponse($response, ['message' => 'User updated successfully']);
    }

    /**
     * Delete a User
     */
    public function deleteUser(Request $request, Response $response, array $args): Response
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            return $this->jsonResponse($response, ['error' => 'Non autorisé / Unauthorized'], 403);
        }

        $userId = $args['id'];

        // Begin transaction for safety
        $this->db->beginTransaction();

        try {
            // Update ride_requests to canceled
            $updateRequests = $this->db->prepare("UPDATE ride_requests SET status = 'canceled' 
                                                 WHERE (passenger_id = :user_id OR driver_id = :user_id) 
                                                 AND status NOT IN ('completed', 'canceled')");
            $updateRequests->execute(['user_id' => $userId]);

            // Delete the user
            $deleteStmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
            $deleteStmt->execute(['id' => $userId]);

            $this->db->commit();

            return $this->jsonResponse($response, ['message' => 'User deleted successfully']);
        } catch (\Exception $e) {
            $this->db->rollBack();
            return $this->jsonResponse($response, ['error' => 'Error deleting user'], 500);
        }
    }

    /**
     * 📊 Return graph data for admin dashboard charts
     * 📈 Retourne les données pour les graphiques du tableau de bord admin
     */
    public function getGraphData(Request $request, Response $response, array $args): Response
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            return $this->jsonResponse($response, ['error' => 'Non autorisé / Unauthorized'], 403);
        }

        // ✅ Carpools per day
        $carpoolsQuery = $this->db->query("
        SELECT DATE(created_at) AS date, COUNT(*) AS count 
        FROM carpools 
        GROUP BY DATE(created_at) 
        ORDER BY date ASC
    ");
        $carpoolsPerDay = $carpoolsQuery->fetchAll(PDO::FETCH_ASSOC);

        // ✅ Credits earned per day
        $creditsQuery = $this->db->query("
        SELECT DATE(created_at) AS date, COUNT(*) * 2 AS credits_earned 
        FROM ride_requests 
        WHERE status = 'completed' 
        GROUP BY DATE(created_at) 
        ORDER BY date ASC
    ");
        $creditsPerDay = $creditsQuery->fetchAll(PDO::FETCH_ASSOC);

        // ✅ Total credits
        $totalQuery = $this->db->query("SELECT COUNT(*) * 2 AS total_credits FROM ride_requests WHERE status = 'completed'");
        $total = $totalQuery->fetch(PDO::FETCH_ASSOC);

        return $this->jsonResponse($response, [
            'carpoolsPerDay' => $carpoolsPerDay,
            'creditsPerDay' => $creditsPerDay,
            'total_credits' => $total['total_credits'] ?? 0
        ]);
    }

    /**
     * Utility method for JSON responses
     */
    private function jsonResponse(Response $response, array $data, int $statusCode = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($statusCode);
    }
}
