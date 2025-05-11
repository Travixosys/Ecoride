<?php

use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use DI\Container;
use Slim\Middleware\MethodOverrideMiddleware;



require __DIR__ . '/../vendor/autoload.php';

// Create Dependency Container
$container = new Container();
AppFactory::setContainer($container);

// Set up Twig (No Caching)
$container->set('view', function () {
    return Twig::create(__DIR__ . '/../app/templates', ['cache' => false]);
});

// Create Slim App
$app = AppFactory::create();

// Middleware: Ensure sessions persist
$app->add(function ($request, $handler) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $handler->handle($request)->withHeader('Access-Control-Allow-Credentials', 'true');
});

$app->add(MethodOverrideMiddleware::class);

// Load Routes
(require __DIR__ . '/../app/routes.php')($app);

// Add Twig Middleware
$app->add(TwigMiddleware::create($app, $container->get('view')));

// Run the App
$app->run();
