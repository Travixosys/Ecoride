<?php
// public/index.php

// Start session once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use MongoDB\Client as MongoClient;

require __DIR__ . '/../vendor/autoload.php';

// ----------------------
// Create DI container
// ----------------------
$container = new Container();
AppFactory::setContainer($container);

// ----------------------
// Register Twig view
// ----------------------
$container->set('view', function () {
    $twig = Twig::create(__DIR__ . '/../app/templates', [
        'cache' => false,
    ]);
    // Make the logged-in user available in all templates
    $currentUser = $_SESSION['user'] ?? null;
    $twig->getEnvironment()->addGlobal('user', $currentUser);
    return $twig;
});

// ----------------------
// Register PDO (MySQL) using Heroku / AlwaysData env vars
// ----------------------
$container->set('db', function () {
    $host     = getenv('DB_HOST') ?: 'mysql-ecoridepool.alwaysdata.net';
    $dbname   = getenv('DB_NAME') ?: 'ecoridepool_eco';
    $user     = getenv('DB_USER') ?: '418123';
    $pass     = getenv('DB_PASS') ?: 'dataSQL45';
    $charset  = 'utf8mb4';

    $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
});

// ----------------------
// Register MongoDB Atlas client & collection
// ----------------------
$container->set('prefsCollection', function () {
    $uri    = getenv('MONGO_URI') ?: 'mongodb+srv://uwaugboaja:6bEKOKuDTtsk2CLf@cluster0.nwx7mtr.mongodb.net/ecoridepool';
    $client = new MongoClient($uri);
    return $client
        ->selectDatabase('ecoridepool')
        ->selectCollection('user_preferences');
});

// ----------------------
// Create and configure Slim App
// ----------------------
$app = AppFactory::create();

// Parse JSON, form data, etc.
$app->addBodyParsingMiddleware();

// Add routing middleware
$app->addRoutingMiddleware();

// Support PUT/DELETE via POST + _METHOD
$app->add(MethodOverrideMiddleware::class);

// Add Twig view middleware
$app->add(TwigMiddleware::create($app, $container->get('view')));

// ----------------------
// Error Middleware (enable full details for debug)
// ----------------------
$errorMiddleware = $app->addErrorMiddleware(
    true,   // displayErrorDetails
    true,   // logErrors
    true    // logErrorDetails
);

// ----------------------
// Load routes and run
// ----------------------
(require __DIR__ . '/../app/routes.php')($app);

$app->run();
