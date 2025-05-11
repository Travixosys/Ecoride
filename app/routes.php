<?php

// ROUTES PRINCIPALES – SLIM 4
// Toutes les routes backend du projet EcoRide
// Comments bilingues FR / EN pour usage oral + technique

use MongoDB\Client;
use Slim\App;
use Slim\Views\Twig;
use App\Controllers\UserController;
use App\Controllers\DriverController;
use App\Controllers\RideController;
use App\Controllers\AdminController;
use App\Models\RideRequest;
use Psr\Container\ContainerInterface;
use MongoDB\Client as MongoDBClient;

return function (App $app) {
    $container = $app->getContainer();
    $twig = Twig::create(__DIR__ . '/../app/templates');

    if (session_status() === PHP_SESSION_NONE) session_start();

    $app->get('/', function ($request, $response) use ($twig) {
        if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin') {
            return $response->withHeader('Location', '/admin')->withStatus(302);
        }
        return $twig->render($response, 'home.twig', ['user' => $_SESSION['user'] ?? null]);
    });

    $app->get('/login', fn($req, $res) => Twig::fromRequest($req)->render($res, 'login.twig'));
    $app->post('/login', fn($req, $res) => (new UserController($container))->login($req, $res));

    $app->get('/request-ride', fn($req, $res) => $container->get('view')->render($res, 'request-ride.twig'));
    $app->post('/request-ride', [RideController::class, 'requestRide']);

    // ✅ Correct route for driver ride history
    $app->get('/driver/ride-history', fn($req, $res) => (new RideController($container))->getDriverRideHistory($req, $res));

    $app->put('/accept-ride/{id}', [RideController::class, 'acceptRide']);
    $app->put('/complete-ride/{id}', [RideController::class, 'completeRide']);
    $app->put('/cancel-ride/{id}', [RideController::class, 'cancelRide']);

    $app->get('/admin', fn($req, $res) => (new AdminController($container))->dashboard($req, $res));
    $app->put('/admin/update-user/{id}', fn($req, $res, $args) => (new AdminController($container))->updateUser($req, $res, $args));
    $app->delete('/admin/delete-user/{id}', fn($req, $res, $args) => (new AdminController($container))->deleteUser($req, $res, $args));
    $app->delete('/admin/delete-ride/{id}', fn($req, $res, $args) => (new AdminController($container))->deleteRide($req, $res, $args));

    $app->get('/profile', function ($req, $res) use ($twig) {
        if (!isset($_SESSION['user'])) return $res->withHeader('Location', '/login')->withStatus(302);
        $mongo = new MongoDBClient("mongodb://localhost:27017");
        $preferences = $mongo->ecoridepool->user_preferences->findOne(['user_id' => $_SESSION['user']['id']]);
        return $twig->render($res, 'profile.twig', [
            'user' => $_SESSION['user'],
            'preferences' => $preferences['preferences'] ?? []
        ]);
    });

    $app->post('/profile/update', fn($req, $res) => (new UserController($container))->updateProfile($req, $res));
    $app->get('/ride-history', fn($req, $res) => (new RideController($container))->getPassengerRideHistory($req, $res));
    $app->get('/active-rides', fn($req, $res) => $twig->render($res, 'active-rides.twig'));
    $app->get('/menu', fn($req, $res) => $container->get('view')->render($res, 'menu.twig', ['user' => $_SESSION['user'] ?? null]));
    $app->get('/carpool', fn($req, $res) => $twig->render($res, 'carpool.twig'));

    $app->post('/driver/create-carpool', [DriverController::class, 'createCarpool'])->setName('create_carpool');
    $app->put('/driver/accept-ride/{id}', [DriverController::class, 'acceptRide'])->setName('accept_ride');

    $app->get('/driver/requests', function ($req, $res) use ($container) {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'driver') {
            return $res->withHeader('Location', '/login')->withStatus(302);
        }
        $requests = (new RideRequest($container->get('db')))->getPending();
        return $container->get('view')->render($res, 'driver-requests.twig', ['rideRequests' => $requests]);
    });
    $app->put('/driver/accept-request/{id}', [RideController::class, 'acceptRide']);
    // $app->put('/driver/accept-ride/{id}', [DriverController::class, 'acceptRide']);
    $app->put('/driver/complete-ride/{id}', [DriverController::class, 'completeRide']);
    $app->put('/driver/cancel-ride/{id}', [DriverController::class, 'cancelRide']);

    $app->get('/register', fn($req, $res) => $twig->render($res, 'register.twig'));
    $app->post('/register', fn($req, $res) => (new UserController($container))->register($req, $res));
    $app->post('/register-driver', fn($req, $res) => (new DriverController($container))->registerDriver($req, $res));

    $app->get('/logout', fn($req, $res) => $res->withHeader('Location', '/menu')->withStatus(302));
    $app->post('/logout', function ($req, $res) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION = [];
        session_destroy();
        setcookie(session_name(), '', time() - 42000, '/home');
        $res->getBody()->write(json_encode(["message" => "Logout successful"]));
        return $res->withHeader('Content-Type', 'application/json');
    });

    $app->get('/maps/route', fn($req, $res) => (new RideController($container))->getRouteData($req, $res));

        /**
     * 📦 API – Gestion des trajets (CRUD)
     * 📦 API – Ride Management (CRUD)
     */

};
// END OF ROUTES