<?php

namespace BattleSnake\Services\Strategy;

use BattleSnake\Models\GameState;
use BattleSnake\Services\SpaceAnalysis\FloodFill;

/**
 * Survival strategy - prioritizes staying alive and maximizing available space
 */
class SurvivalStrategy implements StrategyInterface
{
    private FloodFill $floodFill;
    
    /**
     * Constructor
     */
    public function __construct(FloodFill $floodFill)
    {
        $this->floodFill = $floodFill;
    }
    
    /**
     * Determine the next move for the snake
     * 
     * @param GameState $gameState Current game state
     * @return string Direction ('up', 'down', 'left', 'right')
     */
    public function determineMove(GameState $gameState): string
    {
        $board = $gameState->getBoard();
        $you = $gameState->getYou();
        $head = $you->getHead();
        
        // Get possible moves (avoiding walls and snakes)
        $possibleMoves = $you->getPossibleMoves($board);
        
        // If no moves are possible, return any direction (we're trapped)
        if (empty($possibleMoves)) {
            return 'up';
        }
        
        // Evaluate space for each possible move
        $spaceByMove = $this->floodFill->evaluateMoveSpaces($head, $possibleMoves, $board);
        
        // Calculate hazard penalties
        $hazardPenalties = [];
        foreach ($possibleMoves as $move) {
            $newHead = $head->move($move);
            $hazardPenalties[$move] = $board->hasHazard($newHead) ? 5 : 0;
        }
        
        // Calculate snake head proximity penalties
        $headProximityPenalties = [];
        foreach ($possibleMoves as $move) {
            $newHead = $head->move($move);
            $penalty = 0;
            
            foreach ($board->getSnakes() as $snake) {
                // Skip our own snake
                if ($snake->getId() === $you->getId()) {
                    continue;
                }
                
                $otherHead = $snake->getHead();
                $distance = $newHead->distanceTo($otherHead);
                
                // If very close to another snake head
                if ($distance <= 2) {
                    // If we're smaller or equal, big penalty
                    if ($you->getLength() <= $snake->getLength()) {
                        $penalty += 20;
                    }
                }
            }
            
            $headProximityPenalties[$move] = $penalty;
        }
        
        // Calculate final scores for each move
        $moveScores = [];
        foreach ($possibleMoves as $move) {
            $spaceScore = $spaceByMove[$move];
            $hazardPenalty = $hazardPenalties[$move] ?? 0;
            $headProximityPenalty = $headProximityPenalties[$move] ?? 0;
            
            $moveScores[$move] = $spaceScore - $hazardPenalty - $headProximityPenalty;
        }
        
        // Find move with highest score
        $bestMove = null;
        $bestScore = -PHP_INT_MAX;
        
        foreach ($moveScores as $move => $score) {
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMove = $move;
            }
        }
        
        return $bestMove ?? $possibleMoves[0];
    }
    
    /**
     * Calculate a score for this strategy based on current game state
     * 
     * @param GameState $gameState Current game state
     * @return float Score indicating how appropriate this strategy is (higher is better)
     */
    public function calculateScore(GameState $gameState): float
    {
        $you = $gameState->getYou();
        $board = $gameState->getBoard();
        
        // Base survival score
        $baseScore = 0.5;
        
        // Increase score if board is getting crowded
        $totalBoardSize = $board->getWidth() * $board->getHeight();
        $totalSnakeLength = 0;
        
        foreach ($board->getSnakes() as $snake) {
            $totalSnakeLength += $snake->getLength();
        }
        
        $boardCrowdedness = $totalSnakeLength / $totalBoardSize;
        $crowdednessScore = $boardCrowdedness * 0.3;
        
        // Increase score in mid-late game
        $turnScore = min(0.2, $gameState->getTurn() / 250);
        
        return $baseScore + $crowdednessScore + $turnScore;
    }
}
