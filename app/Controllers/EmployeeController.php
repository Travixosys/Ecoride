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

    public function index(Request $request, Response $response): Response
{
    try {
        // Disputed Carpools
        $stmtDisputed = $this->db->prepare(
            "SELECT c.*, d.name AS driver_name, p.name AS flagged_by
             FROM carpools c
             JOIN users d ON c.driver_id = d.id
             LEFT JOIN ride_requests rr ON rr.carpool_id = c.id AND rr.status = 'disputed'
             LEFT JOIN users p ON rr.passenger_id = p.id
             WHERE c.status = 'disputed'"
        );
        $stmtDisputed->execute();
        $disputed = $stmtDisputed->fetchAll(PDO::FETCH_ASSOC);

        // Resolved Carpools
        $stmtResolved = $this->db->prepare(
            "SELECT c.*, d.name AS driver_name, p.name AS flagged_by
             FROM carpools c
             JOIN users d ON c.driver_id = d.id
             LEFT JOIN ride_requests rr ON rr.carpool_id = c.id AND rr.status = 'disputed'
             LEFT JOIN users p ON rr.passenger_id = p.id
             WHERE c.status = 'resolved'"
        );
        $stmtResolved->execute();
        $resolved = $stmtResolved->fetchAll(PDO::FETCH_ASSOC);

        return $this->view->render($response, 'employee.twig', [
            'disputes' => $disputed,
            'resolved' => $resolved
        ]);
    } catch (\PDOException $e) {
        $response->getBody()->write(json_encode([
            'error' => 'Database error: ' . $e->getMessage()
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
}


    public function viewDispute(Request $request, Response $response, array $args): Response
    {
        $carpoolId = $args['id'];

        try {
            $stmt = $this->db->prepare("
            SELECT c.*, u.name AS driver_name
            FROM carpools c
            JOIN users u ON c.driver_id = u.id
            WHERE c.id = ?
        ");
            $stmt->execute([$carpoolId]);
            $carpool = $stmt->fetch(PDO::FETCH_ASSOC);

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

    public function resolve(Request $request, Response $response, array $args): Response
    {
        $carpoolId = $args['id'];

        $stmt = $this->db->prepare("UPDATE carpools SET status = 'resolved' WHERE id = ?");
        $stmt->execute([$carpoolId]);

        return $response->withHeader('Location', '/employee')->withStatus(302);
    }
}
