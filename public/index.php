<?php
// public/index.php

// Force PHP to show all errors (for development only!)
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Start session once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Csrf\Guard;
use Psr\Http\Message\ResponseFactoryInterface;

require __DIR__ . '/../vendor/autoload.php';

// ────────────────────────────────────────────────────────────
// Load unified database configuration
// ────────────────────────────────────────────────────────────
$dbConfig = require __DIR__ . '/../app/db.php';

// ────────────────────────────────────────────────────────────
// Create DI container & attach to Slim
// ────────────────────────────────────────────────────────────
$container = new Container();
AppFactory::setContainer($container);

// ────────────────────────────────────────────────────────────
// Register Twig view in container
// ────────────────────────────────────────────────────────────
$container->set('view', function () {
    $twig = Twig::create(__DIR__ . '/../app/templates', [
        'cache' => false,
    ]);
    $currentUser = $_SESSION['user'] ?? null;
    $twig->getEnvironment()->addGlobal('user', $currentUser);
    return $twig;
});

// ────────────────────────────────────────────────────────────
// Register PDO (MySQL) from config
// ────────────────────────────────────────────────────────────
$container->set('db', function () use ($dbConfig) {
    return $dbConfig['pdo'];
});

// ────────────────────────────────────────────────────────────
// Register MongoDB collection from config
// ────────────────────────────────────────────────────────────
$container->set('prefsCollection', function () use ($dbConfig) {
    return $dbConfig['prefs_collection'];
});

// ────────────────────────────────────────────────────────────
// Instantiate the Slim app
// ────────────────────────────────────────────────────────────
$app = AppFactory::create();

// ────────────────────────────────────────────────────────────
// Add middleware
// ────────────────────────────────────────────────────────────
// Parse JSON, form data, etc.
$app->addBodyParsingMiddleware();
// Routing
$app->addRoutingMiddleware();
// Support PUT/DELETE via POST + _METHOD
$app->add(MethodOverrideMiddleware::class);
// Twig view rendering
$app->add(TwigMiddleware::create($app, $container->get('view')));

// CSRF protection
// /** @var ResponseFactoryInterface $responseFactory */
// $responseFactory = $app->getResponseFactory();
// $csrf = new Guard($responseFactory);
// $app->add($csrf);

// ────────────────────────────────────────────────────────────
// Error handling (full details while debugging)
// ────────────────────────────────────────────────────────────
$errorMiddleware = $app->addErrorMiddleware(
    true,  // displayErrorDetails
    true,  // logErrors
    true   // logErrorDetails
);

// ────────────────────────────────────────────────────────────
// Load application routes
// ────────────────────────────────────────────────────────────
(require __DIR__ . '/../app/routes.php')($app);

// ────────────────────────────────────────────────────────────
// Run the application
// ────────────────────────────────────────────────────────────
$app->run();
