<?php
//Start session ONCE (if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
} //Start session at the beginning

use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use DI\Container;



require __DIR__ . '/../vendor/autoload.php';

//Create Dependency Container
$container = new Container();
AppFactory::setContainer($container);

//Register Twig in the container
$container->set('view', function () {
    return Twig::create(__DIR__ . '/../app/templates', ['cache' => false]);
});

//Register PDO Database Connection
$container->set('db', function () {
    return new PDO("mysql:host=127.0.0.1;dbname=ecoridepool;charset=utf8", "root", "1707Richi", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
});

//Create Slim App
$app = AppFactory::create();

//Add Twig Middleware (AFTER container setup)
$app->add(TwigMiddleware::create($app, $container->get('view')));

//Load Routes AFTER dependencies are registered
(require __DIR__ . '/../app/routes.php')($app);

//Run the App
$app->run();
