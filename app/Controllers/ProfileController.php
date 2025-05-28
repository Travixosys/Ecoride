<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use MongoDB\Client as MongoDBClient;
use MongoDB\BSON\UTCDateTime;
use PDO;


class ProfileController
{
    protected $view;
    protected $mongo;

    protected $db;

    public function __construct(ContainerInterface $container)
    {
        $this->view = $container->get('view');
        $this->db = $container->get('db');
        $this->mongo = new MongoDBClient("mongodb://localhost:27017");
    }

    public function show(Request $request, Response $response): Response
    {
        if (!isset($_SESSION['user'])) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $userId = $_SESSION['user']['id'];

        // 1. Get user info from MySQL
        $stmt = $this->db->prepare("
        SELECT id, name, email, role, credits, driver_rating 
        FROM users 
        WHERE id = ?
    ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Count completed rides as a passenger
        $rideCountStmt = $this->db->prepare("
        SELECT COUNT(*) FROM ride_requests 
        WHERE passenger_id = :id AND status = 'completed'
    ");
        $rideCountStmt->execute(['id' => $userId]);
        $ridesCompleted = $rideCountStmt->fetchColumn();

        // 3. Fetch MongoDB preferences
        $preferences = $this->mongo
            ->ecoridepool
            ->user_preferences
            ->findOne(['user_id' => $userId]);

        // 4. Fetch reviews written by user
        $stmt = $this->db->prepare("
        SELECT rr.rating, rr.comment, u.name AS driver_name
        FROM ride_reviews rr
        JOIN users u ON rr.target_id = u.id
        WHERE rr.reviewer_id = :id
        ORDER BY rr.created_at DESC
    ");
        $stmt->execute(['id' => $userId]);
        $reviewsWritten = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 5. If driver, fetch received reviews
        $reviewsReceived = [];
        if ($user['role'] === 'driver') {
            $stmt = $this->db->prepare("
            SELECT rr.rating, rr.comment, u.name AS reviewer_name
            FROM ride_reviews rr
            JOIN users u ON rr.reviewer_id = u.id
            WHERE rr.target_id = :id
            ORDER BY rr.created_at DESC
        ");
            $stmt->execute(['id' => $userId]);
            $reviewsReceived = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        // If driver, fetch their vehicle info from MySQL
        $vehicle = null;
        if ($user['role'] === 'driver') {
            $stmt = $this->db->prepare("
        SELECT make, model, year, plate, energy_type, seats 
        FROM vehicles 
        WHERE driver_id = ?
        LIMIT 1
        ");
            $stmt->execute([$userId]);
            $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        // 6. Render profile page
        return $this->view->render($response, 'profile.twig', [
            'user' => $user,
            'preferences' => $preferences['preferences'] ?? [],
            'rides_completed' => $ridesCompleted,
            'reviews_written' => $reviewsWritten,
            'reviews_received' => $reviewsReceived,
            'vehicle' => $vehicle
        ]);
    }


    public function update(Request $request, Response $response): Response
    {
        if (!isset($_SESSION['user'])) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $userId = $_SESSION['user']['id'];
        $parsedBody = $request->getParsedBody();

        // Extract preferences from form data
        $preferences = [
            'smoking_allowed'     => isset($parsedBody['smoking_allowed']) && $parsedBody['smoking_allowed'] === '1',
            'allow_pets'          => isset($parsedBody['allow_pets']) && $parsedBody['allow_pets'] === '1',
            'music_preference'    => $parsedBody['music_preference'] ?? 'None',
            'chat_preference'     => $parsedBody['chat_preference'] ?? 'Casual'
        ];

        // Insert or update MongoDB document WITHOUT updated_at
        $this->mongo
            ->ecoridepool
            ->user_preferences
            ->updateOne(
                ['user_id' => $userId],
                ['$set' => ['preferences' => $preferences]],
                ['upsert' => true]
            );

        $_SESSION['flash'] = 'Preferences saved successfully.';
        return $response->withHeader('Location', '/profile')->withStatus(302);
    }
}
