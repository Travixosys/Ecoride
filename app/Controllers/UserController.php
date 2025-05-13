<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Vehicle;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use PDO;
use PDOException;

class UserController
{
    private PDO $db;
    private User $userModel;
    private Vehicle $vehicleModel;

    public function __construct(ContainerInterface $container)
    {
        $this->db = $container->get('db');
        $this->userModel = new User($this->db);
        $this->vehicleModel = new Vehicle($this->db);
    }

    /**
     * Register a new user (passenger or driver)
     * Inscription d'un utilisateur (passager ou conducteur)
     */
    public function register(Request $request, Response $response): Response
    {
        $body = $request->getBody()->getContents();
        $data = json_decode($body, true) ?? [];

        if (empty($data['name']) || empty($data['email']) || empty($data['password']) || empty($data['role'])) {
            return $this->jsonResponse($response, ['error' => 'Champs requis manquants / Missing required fields'], 400);
        }

        try {
            $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
            $role = strtolower(trim($data['role']));

            if ($role === "passenger") {
                $role = "user";
            } elseif (!in_array($role, ["user", "driver"])) {
                return $this->jsonResponse($response, ['error' => 'Rôle non valide / Invalid role'], 400);
            }

            $this->userModel->createUser([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $hashedPassword,
                'role' => $role,
                'phone_number' => $data['phone_number'] ?? null
            ]);

            $userId = $this->db->lastInsertId();

            if ($role === "driver") {
                if (
                    empty($data['make']) || empty($data['model']) || empty($data['year']) ||
                    empty($data['plate']) || empty($data['seats'])
                ) {
                    return $this->jsonResponse($response, ['error' => 'Détails du véhicule manquants / Missing vehicle details'], 400);
                }

                $this->vehicleModel->create([
                    'driver_id' => $userId,
                    'make' => $data['make'],
                    'model' => $data['model'],
                    'year' => $data['year'],
                    'plate' => $data['plate'],
                    'seats' => $data['seats']
                ]);
            }

            return $this->jsonResponse($response, ['message' => 'Utilisateur enregistré avec succès / User registered successfully'], 201);
        } catch (PDOException $e) {
            error_log("Erreur DB : " . $e->getMessage());
            return $this->jsonResponse($response, ['error' => 'Erreur de base de données / Database error'], 500);
        }
    }

    /**
     * User login
     * Connexion utilisateur
     */

    public function login(Request $request, Response $response): Response
    {
        // Enable error logging
        ini_set('display_errors', 1);
        error_reporting(E_ALL);

        try {
            // Get parsed body instead of raw body
            $data = $request->getParsedBody();

            // Log the received data for debugging
            error_log("Parsed Login Request Body: " . json_encode($data));

            // Detailed input validation
            if ($data === null) {
                error_log("Empty or invalid request body");
                return $this->jsonResponse($response, [
                    'error' => 'Invalid request data',
                    'debug' => 'No data received'
                ], 400);
            }

            // Check for required fields
            if (empty($data['email']) || empty($data['password'])) {
                error_log("Missing Login Credentials");
                return $this->jsonResponse($response, [
                    'error' => 'Missing email or password',
                    'received_data' => $data
                ], 400);
            }

            // Attempt to find user
            $user = $this->userModel->findByEmail($data['email']);

            // Detailed authentication logging
            if (!$user) {
                error_log("User not found: " . $data['email']);
                return $this->jsonResponse($response, [
                    'error' => 'User not found',
                    'email' => $data['email']
                ], 404);
            }

            // Verify password
            if (!password_verify($data['password'], $user['password'])) {
                error_log("Invalid password for email: " . $data['email']);
                return $this->jsonResponse($response, [
                    'error' => 'Invalid credentials'
                ], 401);
            }

            // Start session
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Set session data
            $_SESSION['user'] = [
                "id" => $user['id'],
                "name" => $user['name'],
                "email" => $user['email'],
                "role" => $user['role']
            ];

            // Successful login response
            return $this->jsonResponse($response, [
                'message' => 'Login successful',
                'user' => $_SESSION['user']
            ]);
        } catch (PDOException $e) {
            // Comprehensive error logging
            error_log("Login Database Error: " . $e->getMessage());
            error_log("Error Code: " . $e->getCode());
            error_log("Trace: " . $e->getTraceAsString());

            return $this->jsonResponse($response, [
                'error' => 'Database error',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    // Existing jsonResponse method...

    /**
     * User logout
     * Déconnexion
     */
    public function logout(Request $request, Response $response): Response
    {
        session_start();
        session_destroy();
        return $this->jsonResponse($response, ['message' => 'Déconnexion réussie / Logout successful']);
    }

    /**
     * Update profile (stub – to be implemented)
     * Mise à jour du profil (à compléter)
     */
    public function updateProfile($request, $response)
    {
        $data = $request->getParsedBody();
        return $response->withJson(['message' => 'Profile updated (stub)']);
    }

    /**
     * JSON response wrapper
     * Envoi d'une réponse JSON
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
