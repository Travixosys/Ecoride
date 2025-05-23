<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use PDO;

class ReviewController
{
    protected $db;
    protected $view;

    public function __construct(ContainerInterface $container)
    {
        $this->db = $container->get('db');
        $this->view = $container->get('view');
    }

    /**
     * Submit a new review (User)
     * Soumettre un nouvel avis (Utilisateur)
     */
    public function submit(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = $_SESSION['user']['id'] ?? null; 

        $data = (array) $request->getParsedBody();

        $rideRequestId = $data['ride_request_id'] ?? null;
        $targetId = $data['target_id'] ?? null;
        $rating = $data['rating'] ?? null;
        $comment = $data['comment'] ?? '';

        if (!$rideRequestId || !$targetId || !$rating || !$userId) {
            return $response->withStatus(400);
        }

        // Prevent duplicate review
        $check = $this->db->prepare("SELECT id FROM ride_reviews WHERE ride_request_id = ?");
        $check->execute([$rideRequestId]);
        if ($check->fetch()) {
            return $response->withHeader('Location', '/ride-history')->withStatus(302);
        }

        $stmt = $this->db->prepare("INSERT INTO ride_reviews 
        (ride_request_id, reviewer_id, target_id, rating, comment, created_at)
        VALUES (:ride_request_id, :reviewer_id, :target_id, :rating, :comment, NOW())");
        $stmt->execute([
            ':ride_request_id' => $rideRequestId,
            ':reviewer_id' => $userId,
            ':target_id' => $targetId,
            ':rating' => $rating,
            ':comment' => $comment
        ]);

        return $response->withHeader('Location', '/ride-history')->withStatus(302);
       
    }


    /**
     * View reviews awaiting approval (Employee)
     * Voir les avis en attente de validation (Employé)
     */
    public function moderate(Request $request, Response $response): Response
    {
        $stmt = $this->db->query("SELECT rr.*, u.name AS author_name, r.pickup_location, r.dropoff_location
                                   FROM ride_reviews rr
                                   JOIN users u ON rr.user_id = u.id
                                   JOIN rides r ON rr.ride_id = r.id
                                   WHERE rr.status = 'pending'");

        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view->render($response, 'moderate-reviews.twig', [
            'reviews' => $reviews
        ]);
    }

    public function showReviewForm(Request $request, Response $response, array $args): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = $_SESSION['user']['id'] ?? null;
        $rideRequestId = (int) $args['id'];

        // Fetch the ride request and its driver
        $stmt = $this->db->prepare("
            SELECT rr.*, u.name AS driver_name
            FROM ride_requests rr
            JOIN carpools c ON rr.carpool_id = c.id
            JOIN users u ON c.driver_id = u.id
            WHERE rr.id = :id AND rr.passenger_id = :passenger_id AND rr.status = 'completed'
        ");
        $stmt->execute(['id' => $rideRequestId, 'passenger_id' => $userId]);
        $ride = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ride) {
            $response->getBody()->write("Unauthorized or invalid ride.");
            return $response->withStatus(403);
        }

        return $this->view->render($response, 'review.twig', [
            'ride' => $ride,
            'ride_request_id' => $ride['id'],
            'driver_id' => $ride['driver_id'] ?? $ride['target_id'] ?? null
        ]);
    }

    /**
     * Approve a review (Employee)
     * Approuver un avis (Employé)
     */
    public function approve(Request $request, Response $response, array $args): Response
    {
        $reviewId = $args['id'] ?? null;

        if (!$reviewId) {
            return $response->withStatus(400);
        }

        $stmt = $this->db->prepare("UPDATE ride_reviews SET status = 'approved' WHERE id = ?");
        $stmt->execute([$reviewId]);

        return $response->withHeader('Location', '/employee/reviews')->withStatus(302);
    }

    /**
     * Delete a review (Admin/Employee)
     * Supprimer un avis (Admin/Employé)
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $reviewId = $args['id'] ?? null;

        if (!$reviewId) {
            return $response->withStatus(400);
        }

        $stmt = $this->db->prepare("DELETE FROM ride_reviews WHERE id = ?");
        $stmt->execute([$reviewId]);

        return $response->withHeader('Location', '/employee')->withStatus(302);
    }
}
