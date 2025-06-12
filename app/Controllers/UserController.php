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
                    empty($data['plate']) || empty($data['seats']) || empty($data['energy_type'])
                ) {
                    return $this->jsonResponse($response, ['error' => 'Détails du véhicule manquants / Missing vehicle details'], 400);
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

    /**
     * Handle user login
     * Gère la connexion des utilisateurs
     */
    public function login(Request $request, Response $response): Response
    {
        //  Enable error reporting for debugging
        //  Active l'affichage des erreurs pour le débogage
        ini_set('display_errors', 1);
        error_reporting(E_ALL);

        try {
            // Parse the request body
            // Analyse le corps de la requête
            $data = $request->getParsedBody();

            // 🪵 Log the input data
            // 🪵 Journalise les données reçues
            error_log("Parsed Login Request Body: " . json_encode($data));

            // Check if body is empty or invalid
            // Vérifie si le corps est vide ou invalide
            if ($data === null) {
                error_log("Empty or invalid request body");
                return $this->jsonResponse($response, [
                    'error' => 'Invalid request data',
                    'debug' => 'No data received'
                ], 400);
            }

            //  Ensure both email and password are present
            //  Vérifie que l'email et le mot de passe sont présents
            if (empty($data['email']) || empty($data['password'])) {
                error_log("Missing Login Credentials");
                return $this->jsonResponse($response, [
                    'error' => 'Missing email or password',
                    'received_data' => $data
                ], 400);
            }

            //  Retrieve user by email
            //  Récupère l'utilisateur à partir de son email
            $user = $this->userModel->findByEmail($data['email']);

            if (!$user) {
                // No user found
                // Aucun utilisateur trouvé
                error_log("User not found: " . $data['email']);
                return $this->jsonResponse($response, [
                    'error' => 'User not found',
                    'email' => $data['email']
                ], 404);
            }

            // Check if account is suspended
            // Vérifie si le compte est suspendu
            if (!empty($user['suspended']) && $user['suspended']) {
                error_log("Login attempt by suspended user: " . $data['email']);
                return $this->jsonResponse($response, [
                    'error' => 'Account is suspended. Please contact support.',
                    'fr' => 'Votre compte est suspendu. Veuillez contacter le support.'
                ], 403);
            }

            // 🔐 Validate password
            // 🔐 Vérifie le mot de passe
            if (!password_verify($data['password'], $user['password'])) {
                error_log("Invalid password for email: " . $data['email']);
                return $this->jsonResponse($response, [
                    'error' => 'Invalid credentials',
                    'fr' => 'Identifiants invalides'
                ], 401);
            }

            // Start session if not already started
            // Démarre une session si elle n'est pas déjà active
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Save user data to session
            // Enregistre les données utilisateur dans la session
            $_SESSION['user'] = [
                "id" => $user['id'],
                "name" => $user['name'],
                "email" => $user['email'],
                "role" => $user['role']
            ];

            // Successful login response
            // Réponse de connexion réussie
            return $this->jsonResponse($response, [
                'message' => 'Login successful',
                'fr' => 'Connexion réussie',
                'user' => $_SESSION['user']
            ]);
        } catch (PDOException $e) {
            // Database error handling
            // Gestion des erreurs de base de données
            error_log("Login Database Error: " . $e->getMessage());
            error_log("Error Code: " . $e->getCode());
            error_log("Trace: " . $e->getTraceAsString());

            return $this->jsonResponse($response, [
                'error' => 'Database error',
                'fr' => 'Erreur base de données',
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
