<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use BattleSnake\Controllers\InfoController;
use BattleSnake\Controllers\StartController;
use BattleSnake\Controllers\MoveController;
use BattleSnake\Controllers\EndController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Create Container
$container = new Container();

// Set container to create App with
AppFactory::setContainer($container);

// Create App
$app = AppFactory::create();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Define routes
$app->get('/', [InfoController::class, 'handleRequest']);
$app->post('/start', [StartController::class, 'handleRequest']);
$app->post('/move', [MoveController::class, 'handleRequest']);
$app->post('/end', [EndController::class, 'handleRequest']);

// Add fallback for OPTIONS requests (for CORS)
$app->options('/{routes:.+}', function (Request $request, Response $response) {
    return $response;
});

// Run app
$app->run();
