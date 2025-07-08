
namespace App\Controllers;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class AdminController
{
    /** @var ContainerInterface */
    protected $container;   // FR : Conteneur DI • EN: DI container
    /** @var PDO */
    protected $db;          // FR : Connexion BDD • EN: DB connection
    /** @var \Slim\Views\Twig */
    protected $view;        // FR : Moteur Twig   • EN: Twig view engine

    public function __construct(ContainerInterface $c)
    {
        $this->container = $c;
        $this->db   = $c->get('db');
        $this->view = $c->get('view');
    }

    /*--------------------------------------------------------------------
      dashboard()  
      FR : Affiche le tableau de bord admin  
      EN : Render the admin dashboard
      – Vérifie que l’utilisateur est bien admin            | Checks admin
      – Charge utilisateurs, demandes de trajets, crédits   | Loads users,
        totaux (2 crédits / trajet complété)                | ride requests,
                                                            | total credits
    --------------------------------------------------------------------*/
    public function dashboard(Request $req, Response $res): Response
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            return $this->jsonResponse($res, ['error' => 'Non autorisé / Unauthorized'], 403);
        }

        // FR : Liste des utilisateurs • EN: Users list
        $users = $this->db->query("
            SELECT id, name, email, role, phone_number,
                   COALESCE(license_number,'') AS license_number,
                   COALESCE(suspended,0)      AS suspended
            FROM users
        ")->fetchAll(PDO::FETCH_ASSOC);

        // FR : Demandes de trajet • EN: Ride requests
        $rideRequests = $this->db->query("
            SELECT r.id, r.passenger_id, r.driver_id,
                   r.pickup_location, r.dropoff_location,
                   r.status, r.created_at,
                   p.name AS passenger_name,
                   d.name AS driver_name
            FROM ride_requests r
            LEFT JOIN users p ON r.passenger_id = p.id
            LEFT JOIN users d ON r.driver_id    = d.id
            ORDER BY r.created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // FR : Total des crédits • EN: Total credits
        $total = $this->db->query("
            SELECT COUNT(*)*2 AS total_credits
            FROM ride_requests
            WHERE status='completed'
        ")->fetch(PDO::FETCH_ASSOC);

        return $this->view->render($res, 'admin.twig', [
            'users'         => $users,
            'rides'         => $rideRequests,
            'total_credits' => $total['total_credits'] ?? 0
        ]);
    }

    /*--------------------------------------------------------------------
      updateUser()  
      FR : Met à jour rôle / n° permis                    | EN: Update role /
                                                             driver license
      – Refuse rôle « driver » sans licence              | Rejects driver
      – Répond JSON                                     | Responds JSON
    --------------------------------------------------------------------*/
    public function updateUser(Request $req, Response $res, array $args): Response
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            return $this->jsonResponse($res, ['error' => 'Non autorisé / Unauthorized'], 403);
        }

        $data   = json_decode($req->getBody()->getContents(), true);
        $userId = $args['id'];

        if ($data['role'] === 'driver' && empty($data['license_number'])) {
            return $this->jsonResponse($res, ['error' => 'License number is required for drivers'], 400);
        }

        $this->db->prepare("
            UPDATE users
            SET role = :role, license_number = :lic
            WHERE id  = :id
        ")->execute([
            'role' => $data['role'],
            'lic'  => $data['license_number'] ?? null,
            'id'   => $userId
        ]);

        return $this->jsonResponse($res, ['message' => 'User updated successfully']);
    }

    /*--------------------------------------------------------------------
      deleteUser()  
      FR : Supprime un utilisateur + annule trajets            |
      EN : Delete a user and cancel related rides (TX safety)  |
    --------------------------------------------------------------------*/
    public function deleteUser(Request $req, Response $res, array $args): Response
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            return $this->jsonResponse($res, ['error' => 'Non autorisé / Unauthorized'], 403);
        }

        $uid = $args['id'];
        $this->db->beginTransaction();

        try {
            // FR : Annule ses trajets actifs • EN: Cancel active rides
            $this->db->prepare("
                UPDATE ride_requests
                SET status='canceled'
                WHERE (passenger_id=:u OR driver_id=:u)
                  AND status NOT IN ('completed','canceled')
            ")->execute(['u' => $uid]);

            // FR : Suppression utilisateur • EN: Delete user
            $this->db->prepare("DELETE FROM users WHERE id=:id")
                ->execute(['id' => $uid]);

            $this->db->commit();
            return $this->jsonResponse($res, ['message' => 'User deleted successfully']);
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return $this->jsonResponse($res, ['error' => 'Error deleting user'], 500);
        }
    }

    /*--------------------------------------------------------------------
      getGraphData()  
      FR : Données statistiques pour graphiques admin          |
      EN : Stats data for admin charts (carpools, credits, total)|
    --------------------------------------------------------------------*/
    public function getGraphData(Request $req, Response $res): Response
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            return $this->jsonResponse($res, ['error' => 'Non autorisé / Unauthorized'], 403);
        }

        // FR : Trajets créés / jour • EN: Carpools per day
        $carpoolsPerDay = $this->db->query("
            SELECT DATE(created_at) AS d, COUNT(*) AS c
            FROM carpools
            GROUP BY d ORDER BY d
        ")->fetchAll(PDO::FETCH_ASSOC);

        // FR : Crédits gagnés / jour • EN: Credits earned per day
        $creditsPerDay = $this->db->query("
            SELECT DATE(created_at) AS d, COUNT(*)*2 AS c
            FROM ride_requests
            WHERE status='completed'
            GROUP BY d ORDER BY d
        ")->fetchAll(PDO::FETCH_ASSOC);

        // FR : Total crédits • EN: Total credits
        $total = $this->db->query("
            SELECT COUNT(*)*2 AS total_credits
            FROM ride_requests
            WHERE status='completed'
        ")->fetch(PDO::FETCH_ASSOC);

        return $this->jsonResponse($res, [
            'carpoolsPerDay' => $carpoolsPerDay,
            'creditsPerDay'  => $creditsPerDay,
            'total_credits'  => $total['total_credits'] ?? 0
        ]);
    }

    /*--------------------------------------------------------------------
      jsonResponse()  
      FR : Helper pour réponse JSON • EN: JSON response helper
    --------------------------------------------------------------------*/
    private function jsonResponse(Response $res, array $data, int $code = 200): Response
    {
        $res->getBody()->write(json_encode($data));
        return $res->withHeader('Content-Type', 'application/json')->withStatus($code);
    }
}
