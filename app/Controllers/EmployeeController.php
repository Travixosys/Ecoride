<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use PDO;

class EmployeeController
{
    protected $view;
    protected $db;

    public function __construct(ContainerInterface $container)
    {
        $this->db = $container->get('db');
        $this->view = $container->get('view');
    }

    /**
     * Display employee dashboard (disputes, resolved rides, reviews)
     * Affiche le tableau de bord employé (litiges, trajets résolus, avis)
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            /**
             * 1. Fetch carpools marked as "disputed"
             *    Récupère les trajets dont le statut est "disputed"
             *    LEFT JOIN avec ride_requests pour afficher l'utilisateur ayant signalé
             */
            $stmtDisputed = $this->db->prepare("
                SELECT c.*, d.name AS driver_name, p.name AS flagged_by
                FROM carpools c
                JOIN users d ON c.driver_id = d.id
                LEFT JOIN ride_requests rr ON rr.carpool_id = c.id AND rr.status = 'disputed'
                LEFT JOIN users p ON rr.passenger_id = p.id
                WHERE c.status = 'disputed'
            ");
            $stmtDisputed->execute();
            $disputed = $stmtDisputed->fetchAll(PDO::FETCH_ASSOC);

            /**
             * 2. Fetch carpools marked as "resolved"
             *    Récupère les trajets dont le statut est "resolved"
             */
            $stmtResolved = $this->db->prepare("
                SELECT c.*, d.name AS driver_name, p.name AS flagged_by
                FROM carpools c
                JOIN users d ON c.driver_id = d.id
                LEFT JOIN ride_requests rr ON rr.carpool_id = c.id AND rr.status = 'disputed'
                LEFT JOIN users p ON rr.passenger_id = p.id
                WHERE c.status = 'resolved'
            ");
            $stmtResolved->execute();
            $resolved = $stmtResolved->fetchAll(PDO::FETCH_ASSOC);

            /**
             * 3. Fetch all ride reviews grouped with related data:
             *    - Author's name (users table)
             *    - Ride details (pickup/dropoff from ride_requests)
             *    - Driver's name (via carpools → users)
             *
             *    Récupère tous les avis avec :
             *    - l’auteur
             *    - le conducteur concerné
             *    - la localisation départ / arrivée
             */
            $stmtReviews = $this->db->query("
                SELECT rr.*,
                       u.name AS author_name,
                       r.pickup_location,
                       r.dropoff_location,
                       d.name AS driver_name
                FROM ride_reviews rr
                JOIN users u ON rr.reviewer_id = u.id
                JOIN ride_requests r ON rr.ride_request_id = r.id
                JOIN carpools c ON r.carpool_id = c.id
                JOIN users d ON c.driver_id = d.id
                WHERE rr.status = 'pending'
                ORDER BY rr.created_at DESC
            ");
            $reviews = $stmtReviews->fetchAll(PDO::FETCH_ASSOC);

            // Render the dashboard view
            // Rendu final de la page tableau de bord
            return $this->view->render($response, 'employee.twig', [
                'disputes' => $disputed,
                'resolved' => $resolved,
                'reviews'  => $reviews
            ]);
        } catch (\PDOException $e) {
            // Error handling / Gestion d’erreur base de données
            $response->getBody()->write(json_encode([
                'error' => 'Database error: ' . $e->getMessage()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * View detailed dispute info for a carpool
     * Affiche les détails d’un trajet contesté
     */
    public function viewDispute(Request $request, Response $response, array $args): Response
    {
        $carpoolId = $args['id'];

        try {
            // Get carpool and driver details
            // Récupère les infos conducteur + covoiturage
            $stmt = $this->db->prepare("
                SELECT c.*, u.name AS driver_name
                FROM carpools c
                JOIN users u ON c.driver_id = u.id
                WHERE c.id = ?
            ");
            $stmt->execute([$carpoolId]);
            $carpool = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get users who flagged this carpool
            // Récupère les utilisateurs ayant signalé ce trajet
            $passengersStmt = $this->db->prepare("
                SELECT rr.passenger_id, u.name
                FROM ride_requests rr
                JOIN users u ON rr.passenger_id = u.id
                WHERE rr.carpool_id = ? AND rr.status = 'disputed'
            ");
            $passengersStmt->execute([$carpoolId]);
            $flaggers = $passengersStmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->view->render($response, 'dispute-detail.twig', [
                'carpool' => $carpool,
                'flaggers' => $flaggers
            ]);
        } catch (\PDOException $e) {
            return $response->withStatus(500);
        }
    }

    /**
     * Approve a pending review (statut = approved)
     * Approuve un avis en attente
     */
    public function approveReview(Request $request, Response $response, array $args): Response
    {
        $reviewId = $args['id'];

        $stmt = $this->db->prepare("UPDATE ride_reviews SET status = 'approved' WHERE id = ?");
        $stmt->execute([$reviewId]);

        return $response->withHeader('Location', '/employee')->withStatus(302);
    }

    /**
     * Reject a pending review (statut = rejected)
     * Rejette un avis en attente
     */
    public function rejectReview(Request $request, Response $response, array $args): Response
    {
        $reviewId = $args['id'];

        $stmt = $this->db->prepare("UPDATE ride_reviews SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$reviewId]);

        return $response->withHeader('Location', '/employee')->withStatus(302);
    }

    /**
     * Mark a carpool as resolved
     * Marque un trajet comme résolu (statut = resolved)
     */
    public function resolve(Request $request, Response $response, array $args): Response
    {
        $carpoolId = $args['id'];

        $stmt = $this->db->prepare("UPDATE carpools SET status = 'resolved' WHERE id = ?");
        $stmt->execute([$carpoolId]);

        return $response->withHeader('Location', '/employee')->withStatus(302);
    }
}
