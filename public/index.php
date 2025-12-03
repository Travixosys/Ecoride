<?php
// public/index.php

// Environment detection (set APP_ENV=production in production)
$isProduction = getenv('APP_ENV') === 'production';

// Only show errors in development
ini_set('display_errors', $isProduction ? '0' : '1');
ini_set('display_startup_errors', $isProduction ? '0' : '1');
error_reporting($isProduction ? 0 : E_ALL);

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
$container->set('view', function () use ($container) {
    $twig = Twig::create(__DIR__ . '/../app/templates', [
        'cache' => false,
    ]);
    $currentUser = $_SESSION['user'] ?? null;
    $twig->getEnvironment()->addGlobal('user', $currentUser);

    // Add CSRF token functions for forms
    $twig->getEnvironment()->addFunction(new \Twig\TwigFunction('csrf_tokens', function () use ($container) {
        $csrf = $container->get('csrf');
        $nameKey = $csrf->getTokenNameKey();
        $valueKey = $csrf->getTokenValueKey();
        $name = $csrf->getTokenName();
        $value = $csrf->getTokenValue();
        return '<input type="hidden" name="' . $nameKey . '" value="' . $name . '">' .
               '<input type="hidden" name="' . $valueKey . '" value="' . $value . '">';
    }, ['is_safe' => ['html']]));

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

// CSRF protection with persistent storage
/** @var ResponseFactoryInterface $responseFactory */
$responseFactory = $app->getResponseFactory();
$csrf = new Guard($responseFactory, 'csrf');
$csrf->setPersistentTokenMode(true);
$container->set('csrf', $csrf);
$app->add($csrf);

// ────────────────────────────────────────────────────────────
// Error handling (environment-aware)
// ────────────────────────────────────────────────────────────
$errorMiddleware = $app->addErrorMiddleware(
    !$isProduction,  // displayErrorDetails - only in dev
    true,            // logErrors - always log
    true             // logErrorDetails - always log details
);

// ────────────────────────────────────────────────────────────
// Load application routes
// ────────────────────────────────────────────────────────────
(require __DIR__ . '/../app/routes.php')($app);

// ────────────────────────────────────────────────────────────
// Run the application
// ────────────────────────────────────────────────────────────
$app->run();
