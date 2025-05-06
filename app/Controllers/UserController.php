<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Vehicle;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use PDO;
use PDOException;

class UserController {
    private PDO $db;
    private User $userModel;
    private Vehicle $vehicleModel;

    public function __construct(ContainerInterface $container) {
        $this->db = $container->get('db');
        $this->userModel = new User($this->db);
        $this->vehicleModel = new Vehicle($this->db);
    }

    /**
     * Register a new user (passenger or driver)
     * Inscription d'un utilisateur (passager ou conducteur)
     */
    public function register(Request $request, Response $response): Response {
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
    public function login(Request $request, Response $response): Response {
        $data = json_decode($request->getBody()->getContents(), true) ?? [];

        if (empty($data['email']) || empty($data['password'])) {
            return $this->jsonResponse($response, ['error' => 'Email ou mot de passe manquant / Missing email or password'], 400);
        }

        try {
            $user = $this->userModel->findByEmail($data['email']);

            if (!$user || !password_verify($data['password'], $user['password'])) {
                return $this->jsonResponse($response, ['error' => 'Identifiants invalides / Invalid credentials'], 401);
            }

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $_SESSION['user'] = [
                "id" => $user['id'],
                "name" => $user['name'],
                "email" => $user['email'],
                "role" => $user['role']
            ];

            return $this->jsonResponse($response, [
                'message' => 'Connexion réussie / Login successful',
                'user' => $_SESSION['user']
            ]);

        } catch (PDOException $e) {
            return $this->jsonResponse($response, ['error' => 'Erreur de base de données / Database error'], 500);
        }
    }

    /**
     * User logout
     * Déconnexion
     */
    public function logout(Request $request, Response $response): Response {
        session_start();
        session_destroy();
        return $this->jsonResponse($response, ['message' => 'Déconnexion réussie / Logout successful']);
    }

    /**
     * Update profile (stub – to be implemented)
     * Mise à jour du profil (à compléter)
     */
    public function updateProfile($request, $response) {
        $data = $request->getParsedBody();
        return $response->withJson(['message' => 'Profile updated (stub)']);
    }

    /**
     * JSON response wrapper
     * Envoi d'une réponse JSON
     */
    private function jsonResponse(Response $response, array $data, int $statusCode = 200): Response {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withStatus($statusCode);
    }
}
