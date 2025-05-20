<?php

namespace BattleSnake\Controllers;

use BattleSnake\Config\SnakeConfig;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use BattleSnake\Utils\ResponseFormatter;

class InfoController
{
    /**
     * Handle the GET / request
     * Returns information about the Battlesnake
     */
    public function handleRequest(Request $request, Response $response): Response
    {
        $responseData = [
            'apiversion' => '1',
            'author' => 'Josh Houghtelin',
            'color' => SnakeConfig::SNAKE_COLOR,
            'head' => SnakeConfig::SNAKE_HEAD,
            'tail' => SnakeConfig::SNAKE_TAIL,
            'version' => '1.0.0'
        ];
        
        return ResponseFormatter::formatJsonResponse($response, $responseData);
    }
}
