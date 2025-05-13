<?php


use Slim\App;
use Slim\Views\Twig;
use App\Controllers\UserController;
use App\Controllers\DriverController;
use App\Controllers\RideController;
use App\Controllers\AdminController;
use App\Models\RideRequest;
use MongoDB\Client as MongoDBClient;
use Psr\Container\ContainerInterface;

return function (App $app) {
    $container = $app->getContainer();
    $twig = Twig::create(__DIR__ . '/../app/templates');

    if (session_status() === PHP_SESSION_NONE) session_start();

    // ========================================
    // HOME & LANDING – Page d'accueil générale
    // ========================================
    $app->get('/', function ($request, $response) use ($twig) {
        if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin') {
            return $response->withHeader('Location', '/admin')->withStatus(302);
        }
        return $twig->render($response, 'home.twig', ['user' => $_SESSION['user'] ?? null]);
    });

    // =========================
    // AUTH – Connexion / Login
    // =========================
    $app->get('/login', fn($req, $res) => Twig::fromRequest($req)->render($res, 'login.twig'));
    $app->post('/login', [UserController::class, 'login']);

    
 

    $app->get('/logout', fn($req, $res) => $res->withHeader('Location', '/menu')->withStatus(302));
    $app->post('/logout', function ($req, $res) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION = [];
        session_destroy();
        setcookie(session_name(), '', time() - 42000, '/home');
        $res->getBody()->write(json_encode(["message" => "Logout successful"]));
        return $res->withHeader('Content-Type', 'application/json');
    });

    // ===========================
    // REGISTER – Inscription
    // ===========================
    $app->get('/register', fn($req, $res) => $twig->render($res, 'register.twig'));
    $app->post('/register', [UserController::class, 'register']);
    $app->post('/register-driver', [DriverController::class, 'registerDriver']);

    // ===========================
    // PROFILE – Profil utilisateur
    // ===========================
    $app->get('/profile', function ($req, $res) use ($twig) {
        if (!isset($_SESSION['user'])) return $res->withHeader('Location', '/login')->withStatus(302);
        $mongo = new MongoDBClient("mongodb://localhost:27017");
        $preferences = $mongo->ecoridepool->user_preferences->findOne(['user_id' => $_SESSION['user']['id']]);
        return $twig->render($res, 'profile.twig', [
            'user' => $_SESSION['user'],
            'preferences' => $preferences['preferences'] ?? []
        ]);
    });
    $app->post('/profile/update', [UserController::class, 'updateProfile']);

    // ========================================
    // RIDES – Gestion des trajets (CRUD/API)
    // ========================================
    $app->post('/request-ride', [RideController::class, 'requestRide']);
    $app->get('/ride-history', [RideController::class, 'getPassengerRideHistory']);
    $app->get('/driver/ride-history', [RideController::class, 'getDriverRideHistory']);

    $app->put('/accept-ride/{id}', [RideController::class, 'acceptRide']);
    $app->put('/complete-ride/{id}', [RideController::class, 'completeRide']);
    $app->put('/cancel-ride/{id}', [RideController::class, 'cancelRide']);

    $app->put('/driver/accept-request/{id}', [RideController::class, 'acceptRide']);
    $app->put('/driver/accept-ride/{id}', [RideController::class, 'acceptRide']);
    $app->put('/driver/complete-ride/{id}', [RideController::class, 'completeRide']);
    $app->put('/driver/cancel-ride/{id}', [RideController::class, 'cancelRide']);

    // ================================
    // DRIVER VIEW – Interface conducteur
    // ================================
    $app->get('/driver/requests', function ($req, $res) use ($container) {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'driver') {
            return $res->withHeader('Location', '/login')->withStatus(302);
        }
        $requests = (new RideRequest($container->get('db')))->getPending();
        return $container->get('view')->render($res, 'driver-requests.twig', ['rideRequests' => $requests]);
    });
    $app->post('/driver/create-carpool', [DriverController::class, 'createCarpool'])->setName('create_carpool');

    // ================================
    // ADMIN PANEL – Gestion Admin
    // ================================
    $app->get('/admin', [AdminController::class, 'dashboard']);
    $app->put('/admin/update-user/{id}', [AdminController::class, 'updateUser']);
    $app->delete('/admin/delete-user/{id}', [AdminController::class, 'deleteUser']);
    $app->delete('/admin/delete-ride/{id}', [AdminController::class, 'deleteRide']);

    // =========================
    // STATIC PAGES
    // =========================
    $app->get('/request-ride', fn($req, $res) => $container->get('view')->render($res, 'request-ride.twig'));
    $app->get('/active-rides', fn($req, $res) => $twig->render($res, 'active-rides.twig'));
    $app->get('/menu', fn($req, $res) => $container->get('view')->render($res, 'menu.twig', ['user' => $_SESSION['user'] ?? null]));
    $app->get('/carpool', fn($req, $res) => $twig->render($res, 'carpool.twig'));
    $app->get('/maps/route', [RideController::class, 'getRouteData']);
};

// END OF ROUTES FILE
