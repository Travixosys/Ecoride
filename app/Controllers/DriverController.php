<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use PDO;
use PDOException;

class DriverController {
    private $db;

    public function __construct(ContainerInterface $container) {
        $this->db = $container->get('db');
    }

    /**
     * Enregistrer un conducteur avec ses détails de véhicule
     * Register a driver with vehicle details
     */
    public function registerDriver(Request $request, Response $response): Response {
        $data = json_decode($request->getBody()->getContents(), true) ?? [];

        // Vérifier que les champs utilisateur obligatoires sont présents
        // Check if required user fields are present
        if (empty($data['email']) || empty($data['password']) || empty($data['name']) || empty($data['phone_number'])) {
            return $this->jsonResponse($response, ['error' => 'Champs obligatoires manquants / Missing required fields'], 400);
        }

        // Vérifier les détails du véhicule
        // Validate vehicle details
        if (empty($data['make']) || empty($data['model']) || empty($data['year']) || empty($data['plate']) || empty($data['seats'])) {
            return $this->jsonResponse($response, ['error' => 'Détails du véhicule manquants / Missing vehicle details'], 400);
        }

        try {
            // Hasher le mot de passe
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

            // Insérer le conducteur dans la table users
            // Insert driver into the users table
            $stmt = $this->db->prepare("INSERT INTO users (name, email, password, role, phone_number) 
                                        VALUES (:name, :email, :password, 'driver', :phone_number)");
            $stmt->execute([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $hashedPassword,
                'phone_number' => $data['phone_number']
            ]);

            $driverId = $this->db->lastInsertId();

            // Insérer les détails du véhicule dans la table vehicles
            // Insert vehicle details into vehicles table
            $stmt = $this->db->prepare("INSERT INTO vehicles (driver_id, make, model, year, plate, seats) 
                                        VALUES (:driver_id, :make, :model, :year, :plate, :seats)");
            $stmt->execute([
                'driver_id' => $driverId,
                'make' => $data['make'],
                'model' => $data['model'],
                'year' => $data['year'],
                'plate' => $data['plate'],
                'seats' => $data['seats']
            ]);

            return $this->jsonResponse($response, ['message' => 'Conducteur enregistré avec succès / Driver registered successfully'], 201);

        } catch (PDOException $e) {
            return $this->jsonResponse($response, ['error' => 'Erreur de base de données / Database error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Fonction utilitaire pour la réponse JSON
     * Utility function for JSON response
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
