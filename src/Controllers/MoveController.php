<?php

namespace BattleSnake\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use BattleSnake\Utils\ResponseFormatter;
use BattleSnake\Models\GameState;
use BattleSnake\Services\Strategy\StrategySelector;

class MoveController
{
    private StrategySelector $strategySelector;
    
    public function __construct(StrategySelector $strategySelector)
    {
        $this->strategySelector = $strategySelector;
    }
    
    /**
     * Handle the POST /move request
     * Determines the next move for the snake
     */
    public function handleRequest(Request $request, Response $response): Response
    {
        // Parse request body
        $data = json_decode($request->getBody()->getContents(), true);
        
        // Create game state from request data
        $gameState = new GameState($data);
        
        // Select strategy and determine move
        $strategy = $this->strategySelector->selectStrategy($gameState);
        $move = $strategy->determineMove($gameState);
        
        // Prepare response
        $responseData = [
            'move' => $move,
            'shout' => 'Moving ' . $move . '!'
        ];
        
        return ResponseFormatter::formatJsonResponse($response, $responseData);
    }
}
