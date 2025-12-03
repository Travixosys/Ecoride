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
        // Support both JSON and form data
        $data = $request->getParsedBody();
        if (empty($data)) {
            $body = $request->getBody()->getContents();
            $data = json_decode($body, true) ?? [];
        }

        if (empty($data['name']) || empty($data['email']) || empty($data['password']) || empty($data['role'])) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_error'] = 'Champs requis manquants / Missing required fields';
            return $response->withHeader('Location', '/register')->withStatus(302);
        }

        try {
            $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
            $role = strtolower(trim($data['role']));

            if ($role === "passenger") {
                $role = "user";
            } elseif (!in_array($role, ["user", "driver"])) {
                if (session_status() === PHP_SESSION_NONE) session_start();
                $_SESSION['flash_error'] = 'Rôle non valide / Invalid role';
                return $response->withHeader('Location', '/register')->withStatus(302);
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
                    empty($data['plate']) || empty($data['seats']) || empty($data['energy_type'])
                ) {
                    if (session_status() === PHP_SESSION_NONE) session_start();
                    $_SESSION['flash_error'] = 'Détails du véhicule manquants / Missing vehicle details';
                    return $response->withHeader('Location', '/register')->withStatus(302);
                }

                $this->vehicleModel->create([
                    'driver_id'    => $userId,
                    'make'         => $data['make'],
                    'model'        => $data['model'],
                    'year'         => $data['year'],
                    'plate'        => $data['plate'],
                    'seats'        => $data['seats'],
                    'energy_type'  => $data['energy_type']
                ]);
            }

            // Success - redirect to login page
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_success'] = 'Inscription réussie ! Connectez-vous. / Registration successful! Please login.';
            return $response->withHeader('Location', '/login')->withStatus(302);
        } catch (PDOException $e) {
            error_log("Registration DB Error: " . $e->getMessage());
            if (session_status() === PHP_SESSION_NONE) session_start();
            // Check for duplicate email error
            if ($e->getCode() == 23000) {
                $_SESSION['flash_error'] = 'Email already registered / Email déjà utilisé';
                return $response->withHeader('Location', '/register')->withStatus(302);
            }
            $_SESSION['flash_error'] = 'Database error / Erreur de base de données';
            return $response->withHeader('Location', '/register')->withStatus(302);
        }
    }

    /**
     * User login
     * Connexion utilisateur
     */

    /**
     * Handle user login
     * Gère la connexion des utilisateurs
     */
    public function login(Request $request, Response $response): Response
    {
        try {
            // Parse the request body
            $data = $request->getParsedBody();

            // Check if body is empty or invalid
            if ($data === null) {
                return $this->jsonResponse($response, [
                    'error' => 'Invalid request data / Données invalides'
                ], 400);
            }

            // Ensure both email and password are present
            if (empty($data['email']) || empty($data['password'])) {
                return $this->jsonResponse($response, [
                    'error' => 'Missing email or password / Email ou mot de passe manquant'
                ], 400);
            }

            // Retrieve user by email
            $user = $this->userModel->findByEmail($data['email']);

            // Use generic error message to prevent user enumeration
            if (!$user) {
                error_log("Login failed: user not found for email");
                return $this->jsonResponse($response, [
                    'error' => 'Invalid credentials / Identifiants invalides'
                ], 401);
            }

            // Check if account is suspended
            if (!empty($user['suspended']) && $user['suspended']) {
                error_log("Login attempt by suspended user");
                return $this->jsonResponse($response, [
                    'error' => 'Account is suspended. Please contact support.',
                    'fr' => 'Votre compte est suspendu. Veuillez contacter le support.'
                ], 403);
            }

            // Validate password - use same generic error to prevent enumeration
            if (!password_verify($data['password'], $user['password'])) {
                error_log("Login failed: invalid password");
                return $this->jsonResponse($response, [
                    'error' => 'Invalid credentials / Identifiants invalides'
                ], 401);
            }

            // Start session if not already started
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);

            // Save user data to session
            $_SESSION['user'] = [
                "id" => $user['id'],
                "name" => $user['name'],
                "email" => $user['email'],
                "role" => $user['role']
            ];

            // Successful login response
            return $this->jsonResponse($response, [
                'message' => 'Login successful / Connexion réussie',
                'user' => $_SESSION['user']
            ]);
        } catch (PDOException $e) {
            // Log error details server-side only
            error_log("Login Database Error: " . $e->getMessage());

            // Return generic error to client
            return $this->jsonResponse($response, [
                'error' => 'An error occurred. Please try again. / Une erreur est survenue.'
            ], 500);
        }
    }

    /**
     * User logout
     * Déconnexion
     */
    public function logout(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];
        session_destroy();
        setcookie(session_name(), '', time() - 42000, '/');

        // Detect method (GET = redirect, POST = API)
        if ($request->getMethod() === 'GET') {
            return $response->withHeader('Location', '/menu')->withStatus(302);
        }

        // POST – JSON response
        return $this->jsonResponse($response, ['message' => 'Déconnexion réussie / Logout successful']);
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
