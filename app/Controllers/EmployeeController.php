<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class EmployeeController
{
    protected $view;
    protected $db;

    public function __construct($container)
    {
        $this->view = $container->get('view');
        $this->db = $container->get('db');
    }

    public function index(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'employee') {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $stmt = $this->db->prepare("SELECT * FROM carpools WHERE status = 'disputed'");
        $stmt->execute();
        $disputes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->view->render($response, 'employee.twig', [
            'disputes' => $disputes
        ]);
    }

    public function resolve(Request $request, Response $response, array $args): Response
    {
        $carpoolId = $args['id'];

        $stmt = $this->db->prepare("UPDATE carpools SET status = 'resolved' WHERE id = ?");
        $stmt->execute([$carpoolId]);

        return $response->withHeader('Location', '/employee')->withStatus(302);
    }
}
