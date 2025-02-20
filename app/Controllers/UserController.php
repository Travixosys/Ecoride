<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use PDO;
use PDOException;

class UserController {
    private $db;

    /**
     * Constructeur de la classe UserController.
     * Initialise la connexion à la base de données via le conteneur.
     * 
     * Constructor for UserController.
     * Initializes the database connection via the container.
     */
    public function __construct(ContainerInterface $container) {
        $this->db = $container->get('db');
    }

    /**
     * Inscription d'un nouvel utilisateur (passager ou conducteur).
     * Vérifie et enregistre les utilisateurs dans la base de données.
     * 
     * Register a new user (passenger or driver).
     * Validates and stores users in the database.
     */
    public function register(Request $request, Response $response): Response {
        $body = $request->getBody()->getContents();
        error_log("🔍 Données brutes reçues : " . $body);
    
        $data = json_decode($body, true) ?? [];
        error_log("🔍 Données analysées : " . json_encode($data));
    
        // ✅ Vérification des champs obligatoires (Basic fields validation)
        if (empty($data['name']) || empty($data['email']) || empty($data['password']) || empty($data['role'])) {
            return $this->jsonResponse($response, ['error' => 'Champs requis manquants / Missing required fields'], 400);
        }

        try {
            // ✅ Hash du mot de passe / Password hashing
            $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
            
            // ✅ Vérification et assignation du rôle utilisateur
            // ✅ Role validation and assignment
            $role = strtolower(trim($data['role']));
            if ($role === "passenger") {
                $role = "user"; 
            } elseif ($role !== "user" && $role !== "driver") {
                return $this->jsonResponse($response, ['error' => 'Rôle non valide / Invalid role'], 400);
            }

            // ✅ Enregistrement de l'utilisateur dans la base de données
            // ✅ Insert user into database
            $stmt = $this->db->prepare("INSERT INTO users (name, email, password, role, phone_number) 
                                        VALUES (:name, :email, :password, :role, :phone_number)");
            $stmt->execute([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $hashedPassword,
                'role' => $role,
                'phone_number' => $data['phone_number'] ?? null
            ]);

            $userId = $this->db->lastInsertId();

            // ✅ Si le rôle est conducteur, vérifier et enregistrer les détails du véhicule
            // ✅ If role is driver, validate and insert vehicle details
            if ($role === "driver") {
                if (
                    empty($data['make']) || empty($data['model']) || empty($data['year']) ||
                    empty($data['plate']) || empty($data['seats'])
                ) {
                    return $this->jsonResponse($response, ['error' => 'Détails du véhicule manquants / Missing vehicle details'], 400);
                }

                $stmt = $this->db->prepare("INSERT INTO vehicles (driver_id, make, model, year, plate, seats) 
                                            VALUES (:driver_id, :make, :model, :year, :plate, :seats)");
                $stmt->execute([
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
            error_log("Erreur de base de données / Database error: " . $e->getMessage());
            return $this->jsonResponse($response, ['error' => 'Erreur de base de données / Database error'], 500);
        }
    }

    /**
     * Connexion de l'utilisateur (passager ou conducteur).
     * Vérifie l'identité et stocke les informations de session.
     * 
     * User login (passenger or driver).
     * Validates credentials and stores session information.
     */
    public function login(Request $request, Response $response): Response {
        $data = json_decode($request->getBody()->getContents(), true) ?? [];
    
        if (empty($data['email']) || empty($data['password'])) {
            return $this->jsonResponse($response, ['error' => 'Email ou mot de passe manquant / Missing email or password'], 400);
        }
    
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email' => $data['email']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if (!$user || !password_verify($data['password'], $user['password'])) {
                return $this->jsonResponse($response, ['error' => 'Identifiants invalides / Invalid credentials'], 401);
            }
    
            // ✅ Start session (Handled in middleware, but ensure it's active)
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
    
            // ✅ Store user session
            $_SESSION['user'] = [
                "id" => $user['id'],
                "name" => $user['name'],
                "email" => $user['email'],
                "role" => $user['role']
            ];
    
            return $this->jsonResponse($response, [
                'message' => 'Connexion réussie / Login successful',
                'user' => $_SESSION['user']
            ], 200);
    
        } catch (PDOException $e) {
            return $this->jsonResponse($response, ['error' => 'Erreur de base de données / Database error'], 500);
        }
    }
    
    
    

    /**
     * Déconnexion de l'utilisateur.
     * Détruit la session en cours.
     * 
     * User logout.
     * Destroys the current session.
     */
    public function logout(Request $request, Response $response): Response {
        session_start();
        session_destroy();
        return $this->jsonResponse($response, ['message' => 'Déconnexion réussie / Logout successful'], 200);
    }

    /**
     * Fonction utilitaire pour renvoyer une réponse JSON.
     * 
     * Utility function to send a JSON response.
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
