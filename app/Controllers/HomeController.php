<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HomeController {
    public function index(Request $request, Response $response): Response {
        $response->getBody()->write("<h1>🚀 EcoRide Home is Working!</h1>");
        return $response->withHeader('Content-Type', 'text/html');
    }
}
