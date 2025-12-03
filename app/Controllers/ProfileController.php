<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use Slim\Views\Twig;
use MongoDB\Collection;
use PDO;

class ProfileController
{
    /** @var Twig  Vue Twig • Twig view engine */
    protected Twig $view;
    /** @var PDO   Connexion MySQL • MySQL connection */
    protected PDO $db;
    /** @var Collection  Collection préférences (Mongo) • Mongo prefs collection */
    protected Collection $prefsCollection;

    /*--------------------------------------------------------------------
      __construct()
      FR : Injecte la vue, la BDD MySQL et la collection MongoDB
      EN : Inject Twig view, MySQL DB and MongoDB collection
    --------------------------------------------------------------------*/
    public function __construct(ContainerInterface $container)
    {
        $this->view            = $container->get('view');
        $this->db              = $container->get('db');
        $this->prefsCollection = $container->get('prefsCollection');
    }

    /*--------------------------------------------------------------------
      show()
      FR : Affiche la page de profil utilisateur
      EN : Render user profile page
    --------------------------------------------------------------------*/
    public function show(Request $request, Response $response): Response
    {
        // Redirige vers /login si non connecté • Redirect if not logged-in
        if (!isset($_SESSION['user'])) {
            return $response->withHeader('Location', '/login')
                ->withStatus(302);
        }

        $userId = $_SESSION['user']['id'];

        /* 1) Infos utilisateur (MySQL) -------------------------------- */
        $stmt = $this->db->prepare("
            SELECT id, name, email, role, driver_rating, credits
            FROM users
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        /* 2) Nb de trajets terminés en tant que passager -------------- */
        $rideCountStmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM ride_requests
            WHERE passenger_id = :id
              AND status = 'completed'
        ");
        $rideCountStmt->execute(['id' => $userId]);
        $ridesCompleted = (int)$rideCountStmt->fetchColumn();

        /* 3) Préférences stockées dans Mongo -------------------------- */
        $prefDoc     = $this->prefsCollection->findOne(['user_id' => $userId]);
        $preferences = $prefDoc['preferences'] ?? [];

        /* 4) Avis écrits par l’utilisateur ---------------------------- */
        $stmt = $this->db->prepare("
            SELECT rr.rating, rr.comment, u.name AS driver_name
            FROM ride_reviews rr
            JOIN users u ON rr.target_id = u.id
            WHERE rr.reviewer_id = :id
            ORDER BY rr.created_at DESC
        ");
        $stmt->execute(['id' => $userId]);
        $reviewsWritten = $stmt->fetchAll(PDO::FETCH_ASSOC);

        /* 5) Si conducteur : avis reçus + véhicule -------------------- */
        $reviewsReceived = [];
        $vehicle         = null;
        if ($user['role'] === 'driver') {
            // Avis reçus
            $stmt = $this->db->prepare("
                SELECT rr.rating, rr.comment, u.name AS reviewer_name
                FROM ride_reviews rr
                JOIN users u ON rr.reviewer_id = u.id
                WHERE rr.target_id = :id
                ORDER BY rr.created_at DESC
            ");
            $stmt->execute(['id' => $userId]);
            $reviewsReceived = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Infos véhicule
            $stmt = $this->db->prepare("
                SELECT make, model, year, plate, energy_type, seats
                FROM vehicles
                WHERE driver_id = ?
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        /* 6) Rendu de la vue profil ----------------------------------- */
        return $this->view->render($response, 'profile.twig', [
            'user'             => $user,
            'preferences'      => $preferences,
            'rides_completed'  => $ridesCompleted,
            'reviews_written'  => $reviewsWritten,
            'reviews_received' => $reviewsReceived,
            'vehicle'          => $vehicle,
        ]);
    }

    /*--------------------------------------------------------------------
      update()
      FR : Sauvegarde des préférences utilisateur (MongoDB)
      EN : Save user preferences to MongoDB
    --------------------------------------------------------------------*/
    public function update(Request $request, Response $response): Response
    {
        // Sécurité : doit être connecté • Must be logged-in
        if (!isset($_SESSION['user'])) {
            return $response->withHeader('Location', '/login')
                ->withStatus(302);
        }

        $userId = $_SESSION['user']['id'];
        $data   = $request->getParsedBody();

        /* Construction du tableau preferences • Build preferences array */
        $preferences = [
            'smoking_allowed'  => !empty($data['smoking_allowed']),
            'allow_pets'       => !empty($data['allow_pets']),
            'music_preference' => $data['music_preference'] ?? 'None',
            'chat_preference'  => $data['chat_preference']  ?? 'Casual',
        ];

        /* Upsert dans Mongo (atlas) ----------------------------------- */
        $this->prefsCollection->updateOne(
            ['user_id' => $userId],
            ['$set'    => ['preferences' => $preferences]],
            ['upsert'  => true]
        );

        // Flash message + redirection profil
        $_SESSION['flash'] = 'Preferences saved successfully.';
        return $response->withHeader('Location', '/profile')
            ->withStatus(302);
    }
}
