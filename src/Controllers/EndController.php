<?php

namespace BattleSnake\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use BattleSnake\Utils\ResponseFormatter;
use BattleSnake\Models\GameState;

class EndController
{
    /**
     * Handle the POST /end request
     * Called when a game has ended
     */
    public function handleRequest(Request $request, Response $response): Response
    {
        // Parse request body
        $data = json_decode($request->getBody()->getContents(), true);
        
        // Create game state from request data
        $gameState = new GameState($data);
        
        // Log game end (could be expanded with more detailed logging)
        // No response body needed for this endpoint
        return ResponseFormatter::formatJsonResponse($response, []);
    }
}
