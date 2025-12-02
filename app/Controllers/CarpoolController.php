<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use PDO;
use Slim\Views\Twig;
use MongoDB\Collection;

class CarpoolController
{
    /** @var Twig        Vue Twig • Twig view engine */
    protected Twig $view;

    /** @var PDO         Connexion MySQL • MySQL connection */
    protected PDO $db;

    /** @var Collection  Collection Mongo préférences • Mongo preferences */
    protected Collection $prefsCollection;

    /*--------------------------------------------------------------------
      __construct()
      FR : Injecte vue / BDD / collection Mongo via le conteneur
      EN : Inject view, DB and Mongo collection from the container
    --------------------------------------------------------------------*/
    public function __construct(ContainerInterface $container)
    {
        $this->view  = $container->get('view');
        $this->db    = $container->get('db');
        $this->prefsCollection = $container->get('prefsCollection');
    }

    /*--------------------------------------------------------------------
      listAvailable()
      FR : Retourne la liste filtrée des covoiturages « upcoming »
      EN : Return filtered list of upcoming carpools
    --------------------------------------------------------------------*/
    public function listAvailable(Request $request, Response $response): Response
    {
        // --- 1. Récupère les filtres GET • Fetch query filters -----------
        $params   = $request->getQueryParams();
        $pickup   = $params['pickup']    ?? null;
        $dropoff  = $params['dropoff']   ?? null;
        $minSeats = $params['min_seats'] ?? null;
        $energy   = $params['energy']    ?? null;
        $eco      = $params['eco']       ?? null;

        // --- 2. Requête de base (trajets à venir + places libres) -------
        $sql = "
            SELECT c.*, u.name AS driver_name, v.energy_type
            FROM carpools c
            JOIN users    u ON c.driver_id  = u.id
            JOIN vehicles v ON c.vehicle_id = v.id
            WHERE c.status = 'upcoming'
              AND (c.total_seats - c.occupied_seats) > 0
        ";

        // --- 3. Ajoute conditions dynamiques selon filtres --------------
        $conditions = [];
        $values     = [];

        if ($pickup) {
            $conditions[] = 'c.pickup_location LIKE ?';   // FR : filtre départ • EN : pickup filter
            $values[]     = "%$pickup%";
        }
        if ($dropoff) {
            $conditions[] = 'c.dropoff_location LIKE ?';  // filtre destination
            $values[]     = "%$dropoff%";
        }
        if ($minSeats) {
            $conditions[] = '(c.total_seats - c.occupied_seats) >= ?'; // places min
            $values[]     = (int)$minSeats;
        }
        if ($energy) {
            $conditions[] = 'v.energy_type = ?';          // type énergie
            $values[]     = $energy;
        }
        if ($eco === '1') {
            $conditions[] = "(v.energy_type IN ('electric','hybrid'))"; // option éco
        }

        if ($conditions) {
            $sql .= ' AND ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY c.departure_time ASC';

        // --- 4. Exécute la requête et rend la vue -----------------------
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        $carpools = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view->render($response, 'carpool-list.twig', [
            'carpools' => $carpools,
            'filters'  => compact('pickup', 'dropoff', 'minSeats', 'energy', 'eco'),
        ]);
    }

    /*--------------------------------------------------------------------
      viewDetail()
      FR : Affiche détail d’un covoiturage + prefs conducteur
      EN : Show carpool detail with driver preferences
    --------------------------------------------------------------------*/
    public function viewDetail(Request $request, Response $response, array $args): Response
    {
        $carpoolId = (int)$args['id'];

        // Chargement trajet + véhicule + conducteur
        $stmt = $this->db->prepare("
            SELECT c.*, u.name AS driver_name, u.driver_rating,
                   v.make, v.model, v.energy_type
            FROM carpools c
            JOIN users    u ON c.driver_id  = u.id
            JOIN vehicles v ON c.vehicle_id = v.id
            WHERE c.id = ?
        ");
        $stmt->execute([$carpoolId]);
        $carpool = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$carpool) {
            $response->getBody()->write("Carpool not found.");
            return $response->withStatus(404);
        }

        // Préférences conducteur stockées dans Mongo
        $preferences = null;
        $driverId    = (int)$carpool['driver_id'];
        $mongoResult = $this->prefsCollection->findOne(['user_id' => $driverId]);
        if ($mongoResult && isset($mongoResult['preferences'])) {
            $preferences = json_decode(json_encode($mongoResult['preferences']), true);
        }

        return $this->view->render($response, 'carpool-detail.twig', [
            'carpool'     => $carpool,
            'preferences' => $preferences,
        ]);
    }

    /*--------------------------------------------------------------------
      joinCarpool()
      FR : Passager réserve des places, débit crédits, maj sièges
      EN : Passenger books seats, debits credits, updates seats
    --------------------------------------------------------------------*/
    public function joinCarpool(Request $request, Response $response, array $args): Response
    {
        /* --- Session & contexte utilisateur ---------------------------
           FR : On s’assure que la session existe puis on récupère
                l’identifiant et le rôle de l’utilisateur courant.
           EN : Ensure session started, fetch current user id / role.
        ----------------------------------------------------------------*/
        if (session_status() === PHP_SESSION_NONE) session_start();
        $carpoolId      = (int)$args['id'];
        $user           = $_SESSION['user'] ?? null;
        $userId         = $user['id']   ?? null;
        $role           = $user['role'] ?? null;

        // Redirection si non connecté • Redirect if not logged in
        if (!$userId) {
            return $response
                ->withHeader('Location', "/register?redirect=/carpools/{$carpoolId}")
                ->withStatus(302);
        }

        /* --- Lecture des données formulaire ---------------------------
           FR : Nombre de places demandées, coût et commission par place
           EN : Seats requested, seat cost, platform commission
        ----------------------------------------------------------------*/
        $data           = $request->getParsedBody();
        $requestedSeats = max(1, (int)($data['passenger_count'] ?? 1));
        $costPerSeat    = 5;
        $commissionPerSeat = 2;

        $totalCost   = $requestedSeats * $costPerSeat;      // prix total
        $commission  = $requestedSeats * $commissionPerSeat; // part plateforme
        // (le net conducteur sera versé lors de la complétion)

        try {
            // Start transaction to prevent race conditions
            $this->db->beginTransaction();

            /* --- Chargement du covoiturage avec verrouillage ------------- */
            // Use FOR UPDATE to lock the row and prevent concurrent bookings
            $stmt = $this->db->prepare("SELECT * FROM carpools WHERE id = ? FOR UPDATE");
            $stmt->execute([$carpoolId]);
            $carpool = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$carpool) {
                $this->db->rollBack();
                $response->getBody()->write("Carpool not found.");
                return $response->withStatus(404);
            }

            /* --- Driver cannot join their OWN carpool -------------------- */
            if ((int)$carpool['driver_id'] === (int)$userId) {
                $this->db->rollBack();
                return $this->reloadCarpoolWithMessage(
                    $response,
                    $carpoolId,
                    "You cannot join your own carpool."
                );
            }

            /* --- Vérification des places disponibles --------------------- */
            $availableSeats = $carpool['total_seats'] - $carpool['occupied_seats'];
            if ($requestedSeats > $availableSeats) {
                $this->db->rollBack();
                return $this->reloadCarpoolWithMessage(
                    $response,
                    $carpoolId,
                    "Not enough available seats. Only {$availableSeats} left."
                );
            }

            /* --- Vérification des crédits passager ----------------------- */
            $stmt = $this->db->prepare("SELECT credits FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $userCredits = $stmt->fetchColumn();
            if ($userCredits === false || $userCredits < $totalCost) {
                $this->db->rollBack();
                return $this->reloadCarpoolWithMessage(
                    $response,
                    $carpoolId,
                    "You need {$totalCost} credits to join. You have " . ($userCredits ?? 0) . "."
                );
            }

            /* --- Prévention des doubles réservations --------------------- */
            $stmt = $this->db->prepare("
                SELECT id FROM ride_requests
                WHERE passenger_id = ? AND carpool_id = ?
            ");
            $stmt->execute([$userId, $carpoolId]);
            if ($stmt->fetch()) {
                $this->db->rollBack();
                return $this->reloadCarpoolWithMessage(
                    $response,
                    $carpoolId,
                    "You have already joined this ride."
                );
            }

            /* --- INSERT réservation + commission ------------------------- */
            $this->db->prepare("
                INSERT INTO ride_requests
                   (passenger_id, driver_id, carpool_id,
                    pickup_location, dropoff_location,
                    passenger_count, status, created_at, commission)
                VALUES (?, ?, ?, ?, ?, ?, 'accepted', NOW(), ?)
            ")->execute([
                $userId,
                $carpool['driver_id'],
                $carpoolId,
                $carpool['pickup_location'],
                $carpool['dropoff_location'],
                $requestedSeats,
                $commission
            ]);

            /* ---------------------------------------------------------------
               FR : Incrémente le nombre de sièges occupés
               EN : Increment occupied seat count
            --------------------------------------------------------------- */
            $this->db->prepare("
                UPDATE carpools
                   SET occupied_seats = occupied_seats + ?
                 WHERE id = ?
            ")->execute([$requestedSeats, $carpoolId]);

            /* ---------------------------------------------------------------
               FR : Débite le passager du montant total
               EN : Debit passenger's credits
            --------------------------------------------------------------- */
            $this->db->prepare("
                UPDATE users
                   SET credits = credits - ?
                 WHERE id = ?
            ")->execute([$totalCost, $userId]);

            // Commit the transaction
            $this->db->commit();

            // Retourne la page détail avec message de succès
            return $this->reloadCarpoolWithMessage(
                $response,
                $carpoolId,
                "Successfully joined. {$totalCost} credits deducted (platform cut: {$commission})."
            );

        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("joinCarpool error: " . $e->getMessage());
            return $this->reloadCarpoolWithMessage(
                $response,
                $carpoolId,
                "An error occurred while processing your request. Please try again."
            );
        }
    }



    /* ------------------------------------------------------------------
       startCarpool()
       FR : Le conducteur démarre le trajet (status → in progress)
       EN : Driver starts the carpool (status → in progress)
    ------------------------------------------------------------------ */
    public function startCarpool(Request $request, Response $response, array $args): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'driver') {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $carpoolId = (int)$args['id'];
        $driverId = $_SESSION['user']['id'];

        // Verify ownership: only the driver who created the carpool can start it
        $stmt = $this->db->prepare("SELECT * FROM carpools WHERE id = ? AND driver_id = ?");
        $stmt->execute([$carpoolId, $driverId]);
        $carpool = $stmt->fetch(PDO::FETCH_ASSOC);

        // 404 si le trajet n'existe pas ou n'appartient pas au conducteur
        if (!$carpool) {
            $response->getBody()->write("Carpool not found or not authorized.");
            return $response->withStatus(404);
        }

        // Refuse de démarrer si aucun passager
        if ((int)$carpool['occupied_seats'] === 0) {
            $response->getBody()->write("Cannot start ride with 0 passengers.");
            return $response->withStatus(400);
        }

        // Passage au statut "in progress"
        $this->db->prepare("UPDATE carpools SET status = 'in progress' WHERE id = ?")
            ->execute([$carpoolId]);

        return $response->withHeader('Location', '/driver/dashboard')->withStatus(302);
    }

    /**
     * completeCarpool()
     * FR : Le conducteur clôture le trajet, la plateforme prélève sa
     *      commission et crédite le net du conducteur.
     * EN : Driver completes the ride; platform keeps commission and
     *      credits driver’s net earnings.
     */
    public function completeCarpool(Request $request, Response $response, array $args): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $driverId  = $_SESSION['user']['id'] ?? null;
        $carpoolId = (int)$args['id'];

        /* Vérifie que le trajet appartient bien au conducteur et
           qu’il est actuellement « in progress » */
        $stmt = $this->db->prepare("
            SELECT status
            FROM carpools
            WHERE id = :id AND driver_id = :driver_id
        ");
        $stmt->execute([
            'id'        => $carpoolId,
            'driver_id' => $driverId
        ]);
        $carpool = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$carpool || $carpool['status'] !== 'in progress') {
            // 403 : action non autorisée
            return $response->withStatus(403);
        }

        try {
            $this->db->beginTransaction();

            /* 1) Met le covoiturage à “completed” */
            $this->db->prepare("
                UPDATE carpools
                SET status = 'completed',
                    updated_at = NOW()
                WHERE id = :id
            ")->execute(['id' => $carpoolId]);

            /* 2) Passe toutes les demandes ‘accepted’ à ‘completed’ */
            $this->db->prepare("
                UPDATE ride_requests
                SET status = 'completed',
                    completed_at = NOW()
                WHERE carpool_id = :id
                  AND status     = 'accepted'
            ")->execute(['id' => $carpoolId]);

            /* 3) Calcule :
                  - total_fares    : crédits payés par passagers
                  - total_commission : part plateforme (2 crédit/siège)
                  - total_driver_net : ce qui revient au conducteur       */
            $totalsStmt = $this->db->prepare("
                SELECT
                  SUM(passenger_count * 5)                   AS total_fares,
                  SUM(commission)                            AS total_commission,
                  (SUM(passenger_count * 5) - SUM(commission)) AS total_driver_net
                FROM ride_requests
                WHERE carpool_id = :id
                  AND status     = 'completed'
            ");
            $totalsStmt->execute(['id' => $carpoolId]);
            $t                   = $totalsStmt->fetch(PDO::FETCH_ASSOC);
            $driverNet           = (int)($t['total_driver_net']   ?? 0);
            $platformCommission  = (int)($t['total_commission']  ?? 0);

            /* 4) Crédite le conducteur si net > 0 */
            if ($driverNet > 0) {
                $this->db->prepare("
                    UPDATE users
                    SET credits = credits + :amount
                    WHERE id    = :driver_id
                ")->execute([
                    'amount'    => $driverNet,
                    'driver_id' => $driverId
                ]);
            }

            /* 5) Commit transaction et redirection tableau de bord
               5) Commit transaction and redirect to driver dashboard */
            $this->db->commit();
            return $response
                ->withHeader('Location', '/driver/dashboard')
                ->withStatus(302);
        } catch (\PDOException $e) {
            /* Rollback + retour JSON 500 en cas d'erreur SQL
                  Rollback + return JSON 500 on DB error */
            $this->db->rollBack();
            error_log("completeCarpool error: " . $e->getMessage());
            $payload = json_encode([
                'error' => 'An error occurred while completing the carpool'
            ]);
            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /* ------------------------------------------------------------------
          createForm()
          FR : Affiche le formulaire “Offrir un covoiturage” (liste véhicules)
          EN : Show “Offer a carpool” form (driver vehicles list)
       ------------------------------------------------------------------ */
    public function createForm(Request $request, Response $response): Response
    {
        $userId = $_SESSION['user']['id'] ?? null;
        $stmt   = $this->db->prepare("SELECT * FROM vehicles WHERE driver_id = ?");
        $stmt->execute([$userId]);
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->view->render($response, 'carpool-create.twig', [
            'vehicles' => $vehicles
        ]);
    }

    /* ------------------------------------------------------------------
          storeCarpool()
          FR : Enregistre un nouveau trajet (status = 'upcoming')
          EN : Persist a new upcoming carpool
       ------------------------------------------------------------------ */
    public function storeCarpool(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Authorization check - only drivers can create carpools
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'driver') {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $data   = $request->getParsedBody();
        $userId = $_SESSION['user']['id'];

        // Input validation
        $vehicleId = $data['vehicle_id'] ?? null;
        $pickup = trim($data['pickup_location'] ?? '');
        $dropoff = trim($data['dropoff_location'] ?? '');
        $departureTime = $data['departure_time'] ?? null;
        $totalSeats = (int)($data['total_seats'] ?? 0);

        // Validate required fields
        if (empty($vehicleId) || empty($pickup) || empty($dropoff) || empty($departureTime) || $totalSeats < 1) {
            $_SESSION['flash_error'] = 'All fields are required and seats must be at least 1.';
            return $response->withHeader('Location', '/driver/carpools/create')->withStatus(302);
        }

        // Validate vehicle belongs to this driver
        $stmt = $this->db->prepare("SELECT id FROM vehicles WHERE id = ? AND driver_id = ?");
        $stmt->execute([$vehicleId, $userId]);
        if (!$stmt->fetch()) {
            $_SESSION['flash_error'] = 'Invalid vehicle selected.';
            return $response->withHeader('Location', '/driver/carpools/create')->withStatus(302);
        }

        // Validate departure time is in the future
        $departureTimestamp = strtotime($departureTime);
        if ($departureTimestamp === false || $departureTimestamp <= time()) {
            $_SESSION['flash_error'] = 'Departure time must be in the future.';
            return $response->withHeader('Location', '/driver/carpools/create')->withStatus(302);
        }

        // Validate seats (reasonable limit)
        if ($totalSeats > 50) {
            $_SESSION['flash_error'] = 'Maximum 50 seats allowed.';
            return $response->withHeader('Location', '/driver/carpools/create')->withStatus(302);
        }

        try {
            $stmt = $this->db->prepare("
                   INSERT INTO carpools
                       (driver_id, vehicle_id, pickup_location, dropoff_location,
                        departure_time, total_seats, occupied_seats, status, created_at)
                   VALUES (?, ?, ?, ?, ?, ?, 0, 'upcoming', NOW())
               ");
            $stmt->execute([
                $userId,
                $vehicleId,
                $pickup,
                $dropoff,
                $departureTime,
                $totalSeats
            ]);

            return $response->withHeader('Location', '/carpools')->withStatus(302);
        } catch (\PDOException $e) {
            error_log("storeCarpool error: " . $e->getMessage());
            $_SESSION['flash_error'] = 'An error occurred while creating the carpool.';
            return $response->withHeader('Location', '/driver/carpools/create')->withStatus(302);
        }
    }

    /* ------------------------------------------------------------------
          reloadCarpoolWithMessage()
          FR : Helper – recharge la page détail avec un message flash
          EN : Helper – re-render carpool detail with flash message
       ------------------------------------------------------------------ */
    private function reloadCarpoolWithMessage(Response $response, int $carpoolId, string $joinMessage): Response
    {
        // Re-charge les infos trajet + véhicule + conducteur
        $stmt = $this->db->prepare("
               SELECT c.*, u.name AS driver_name, u.driver_rating,
                      v.make, v.model, v.energy_type
               FROM carpools c
               JOIN users    u ON c.driver_id  = u.id
               JOIN vehicles v ON c.vehicle_id = v.id
               WHERE c.id = ?
           ");
        $stmt->execute([$carpoolId]);
        $carpool = $stmt->fetch(PDO::FETCH_ASSOC);

        // Préférences conducteur (Mongo)
        $preferences = null;
        if ($carpool) {
            $prefDoc = $this->prefsCollection->findOne(['user_id' => (int)$carpool['driver_id']]);
            if ($prefDoc && isset($prefDoc['preferences'])) {
                $preferences = json_decode(json_encode($prefDoc['preferences']), true);
            }
        }

        // Rend la vue détail avec le message
        return $this->view->render($response, 'carpool-detail.twig', [
            'carpool'      => $carpool,
            'preferences'  => $preferences,
            'join_message' => $joinMessage,
        ]);
    }
}
