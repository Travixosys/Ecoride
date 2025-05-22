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
        $data = (array) $request->getParsedBody();

        // Required fields: ride_id, rating, comment
        $rideId = $data['ride_id'] ?? null;
        $rating = $data['rating'] ?? null;
        $comment = $data['comment'] ?? '';
        $userId = $_SESSION['user']['id'] ?? null;

        if (!$rideId || !$rating || !$userId) {
            return $response->withStatus(400);
        }

        $stmt = $this->db->prepare("INSERT INTO ride_reviews (ride_id, user_id, rating, comment, status, created_at)
                                    VALUES (:ride_id, :user_id, :rating, :comment, 'pending', NOW())");
        $stmt->execute([
            ':ride_id' => $rideId,
            ':user_id' => $userId,
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

        return $response->withHeader('Location', '/employee/reviews')->withStatus(302);
    }
}
