<?php

use MongoDB\Client;
use Slim\App;
use Slim\Views\Twig;
use App\Controllers\UserController;
use App\Controllers\DriverController;
use App\Controllers\RideController;
use Psr\Container\ContainerInterface;

use MongoDB\Client as MongoDBClient;

return function (App $app) {
    $container = $app->getContainer();
    $twig = Twig::create(__DIR__ . '/../app/templates');

    // Vérifier si la session est active avant de la démarrer
    // Check if session is active before starting it
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    /**
     * Page d'accueil
     * Home Page
     */
    $app->get('/', function ($request, $response) use ($twig) {
        if (isset($_SESSION['user'])) {
            if ($_SESSION['user']['role'] === 'admin') {
                return $response->withHeader('Location', '/admin')->withStatus(302);
            }
            return $response->withHeader('Location', '/menu')->withStatus(302);
        }
    
        // Render the home.twig template if no redirection occurs
        return $twig->render($response, 'home.twig', ['user' => $_SESSION['user'] ?? null]);
    });
    /**
     * Page de connexion (Affichage)
     * Login Page (View)
     */
    $app->get('/login', function ($request, $response) {
        $view = Twig::fromRequest($request);
        return $view->render($response, 'login.twig');
    });

    /**
     * Connexion utilisateur (POST)
     * User Login (POST)
     */
    $app->post('/login', function ($request, $response) use ($container) {
        $controller = new UserController($container);
        return $controller->login($request, $response);
    });

    /**
     * Historique des trajets du conducteur
     * Driver Ride History
     */
    $app->get('/driver/ride-history', function ($request, $response) use ($container) {
        $controller = new RideController($container);
        return $controller->getDriverRideHistory($request, $response);
    });

    /**
     * Page d'inscription (Affichage)
     * Registration Page (View)
     */
    $app->get('/register', function ($request, $response) use ($twig) {
        return $twig->render($response, 'register.twig');
    });

    $app->get('/admin', function ($request, $response) use ($container) {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            return $response->withStatus(403)->withJson(['error' => 'Non autorisé / Unauthorized']);
        }

        $db = $container->get('db');
        $stmt = $db->query("SELECT id, name, email, role, phone_number, 
        COALESCE(license_number, '') AS license_number FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);


        return $container->get('view')->render($response, 'admin.twig', ['users' => $users]);
    });


    $app->put('/admin/update-user/{id}', function ($request, $response, $args) use ($container) {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            return $response->withStatus(403)->withJson(['error' => 'Non autorisé / Unauthorized']);
        }

        $db = $container->get('db');
        $data = json_decode($request->getBody()->getContents(), true);
        $userId = $args['id'];

        // ✅ Ensure license number updates only for drivers
        if ($data['role'] === 'driver' && empty($data['license_number'])) {
            return $response->withJson(['error' => 'License number is required for drivers'], 400);
        }

        $stmt = $db->prepare("UPDATE users SET role = :role, license_number = :license WHERE id = :id");
        $stmt->execute([
            'role' => $data['role'],
            'license' => $data['license_number'] ?? null,
            'id' => $userId
        ]);

        return $response->withJson(['message' => 'Utilisateur mis à jour avec succès / User updated successfully']);
    });



    /**
     * Page de profil
     * Profile Page
     */
    /**
     * ✅ Profile Page (Fetching MongoDB Preferences)
     */
    $app->get('/profile', function ($request, $response) use ($twig) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    
        if (!isset($_SESSION['user'])) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }
    
        $mongo = new MongoDBClient("mongodb://localhost:27017");
        $collection = $mongo->ecoridepool->user_preferences;
    
        $user = $_SESSION['user'];
        $preferences = $collection->findOne(['user_id' => $user['id']]);
    
        error_log("🔍 MongoDB Preferences: " . json_encode($preferences)); // Debugging log
    
        return $twig->render($response, 'profile.twig', [
            'user' => $user,
            'preferences' => $preferences['preferences'] ?? []
        ]);
    });
    

    /**
     * Historique des trajets du passager
     * Passenger Ride History
     */
    $app->get('/ride-history', function ($request, $response) use ($container) {
        $controller = new RideController($container);
        return $controller->getPassengerRideHistory($request, $response);
    });

    /**
     * Affichage des trajets en cours
     * Active Rides Page
     */
    $app->get('/active-rides', function ($request, $response) use ($twig) {
        return $twig->render($response, 'active-rides.twig');
    });

    /**
     * Page du menu
     * Menu Page
     */
    $app->get('/menu', function ($request, $response, $args) use ($container) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start(); // ✅ Ensure session starts
        }

        // ✅ Debugging: Log session data
        error_log("🔍 Menu Page Session Data: " . json_encode($_SESSION));

        $user = $_SESSION['user'] ?? null;

        return $container->get('view')->render($response, 'menu.twig', [
            'user' => $user //  Pass user data to Twig
        ]);
    });

    //  GET - Show Ride Request Form
    $app->get('/request-ride', function ($request, $response) use ($container) {
        return $container->get('view')->render($response, 'request-ride.twig');
    });

    // POST - Handle Ride Request Submission
    $app->post('/request-ride', function ($request, $response) use ($container) {
        // ✅ Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // ✅ Check if user is logged in
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'user') {
            $response->getBody()->write(json_encode(["error" => "Non autorisé / Unauthorized"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        // ✅ Get passenger ID
        $passenger_id = $_SESSION['user']['id'];

        // ✅ Parse request data
        $data = json_decode($request->getBody()->getContents(), true);
        if (empty($data['pickup_location']) || empty($data['dropoff_location'])) {
            $response->getBody()->write(json_encode(["error" => "Champs requis manquants / Missing required fields"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            // ✅ Insert ride request
            $stmt = $container->get('db')->prepare("
                INSERT INTO rides (passenger_id, pickup_location, dropoff_location, status) 
                VALUES (:passenger_id, :pickup, :dropoff, 'pending')
            ");
            $stmt->execute([
                'passenger_id' => $passenger_id,
                'pickup' => $data['pickup_location'],
                'dropoff' => $data['dropoff_location']
            ]);

            $response->getBody()->write(json_encode(["message" => "Trajet demandé avec succès / Ride request submitted successfully"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(["error" => "Erreur du serveur / Server error"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });



    /**
     * Page de covoiturage
     * Carpool Page
     */
    $app->get('/carpool', function ($request, $response) use ($twig) {
        return $twig->render($response, 'carpool.twig');
    });

    /**
     * Inscription d'un passager (POST)
     * Passenger Registration (POST)
     */
    $app->post('/register', function ($request, $response) use ($container) {
        $controller = new UserController($container);
        return $controller->register($request, $response);
    });

    /**
     * Inscription d'un conducteur (POST)
     * Driver Registration (POST)
     */
    $app->post('/register-driver', function ($request, $response) use ($container) {
        $controller = new DriverController($container);
        return $controller->registerDriver($request, $response);
    });

    /**
     * Déconnexion de l'utilisateur
     * User Logout
     */
    $app->get('/logout', function ($request, $response) {
        return $response->withHeader('Location', '/menu')->withStatus(302);
    });

    $app->post('/logout', function ($request, $response) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // ✅ Destroy session properly
        $_SESSION = [];
        session_destroy();
        setcookie(session_name(), '', time() - 42000, '/home');

        $payload = json_encode(["message" => "Logout successful"]);

        // ✅ Proper response handling for JSON output
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    });

    /**
     * Gestion des trajets (CRUD)
     * Ride Management (CRUD)
     */
    // $app->post('/request-ride', [RideController::class, 'requestRide']);
    $app->get('/open-rides', [RideController::class, 'getOpenRides']);
    $app->put('/accept-ride/{id}', [RideController::class, 'acceptRide']);
    $app->put('/complete-ride/{id}', [RideController::class, 'completeRide']);
    $app->put('/cancel-ride/{id}', [RideController::class, 'cancelRide']);
};
