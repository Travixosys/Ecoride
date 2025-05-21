<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use PDO;

class CarpoolController
{
    protected $view;
    protected $db;
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->view = $container->get('view');
        $this->db = $container->get('db');
    }

    // (rest of the methods stay the same)


    /**
     * Display all available carpools
     * Affiche tous les trajets disponibles
     */
    public function listAvailable(Request $request, Response $response): Response
    {
        $stmt = $this->db->prepare("
            SELECT c.*, u.name AS driver_name, v.energy_type
            FROM carpools c
            JOIN users u ON c.driver_id = u.id
            JOIN vehicles v ON c.vehicle_id = v.id
            WHERE c.status = 'upcoming' AND (c.total_seats - c.occupied_seats) > 0
            ORDER BY c.departure_time ASC
        ");
        $stmt->execute();
        $carpools = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view->render($response, 'carpool-list.twig', [
            'carpools' => $carpools
        ]);
    }

    /**
     * Show form to offer a new carpool (driver)
     * Affiche le formulaire pour proposer un nouveau covoiturage (conducteur)
     */
    public function createForm(Request $request, Response $response): Response
    {
        $userId = $_SESSION['user']['id'] ?? null;

        $stmt = $this->db->prepare("SELECT * FROM vehicles WHERE driver_id = ?");
        $stmt->execute([$userId]);
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view->render($response, 'carpool-create.twig', [
            'vehicles' => $vehicles
        ]);
    }

    /**
     * Save a new carpool offered by the driver
     * Enregistre un nouveau covoiturage proposé par le conducteur
     */
    public function storeCarpool(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $userId = $_SESSION['user']['id'] ?? null;

        $stmt = $this->db->prepare("
            INSERT INTO carpools (driver_id, vehicle_id, pickup_location, dropoff_location, departure_time, total_seats, occupied_seats, status)
            VALUES (?, ?, ?, ?, ?, ?, 0, 'upcoming')
        ");
        $stmt->execute([
            $userId,
            $data['vehicle_id'],
            $data['pickup_location'],
            $data['dropoff_location'],
            $data['departure_time'],
            $data['total_seats']
        ]);

        return $response
            ->withHeader('Location', '/carpools')
            ->withStatus(302);
    }
}
