<?php

namespace BattleSnake\Services\Strategy;

use BattleSnake\Models\GameState;
use BattleSnake\Services\PathFinding\PathFinderInterface;
use BattleSnake\Services\SpaceAnalysis\FloodFill;

/**
 * Aggressive strategy - prioritizes attacking other snakes
 */
class AggressiveStrategy implements StrategyInterface
{
    private PathFinderInterface $pathFinder;
    private FloodFill $floodFill;
    
    /**
     * Constructor
     */
    public function __construct(PathFinderInterface $pathFinder, FloodFill $floodFill)
    {
        $this->pathFinder = $pathFinder;
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
        
        // Find smaller snakes to target
        $targets = [];
        foreach ($board->getSnakes() as $snake) {
            // Skip our own snake
            if ($snake->getId() === $you->getId()) {
                continue;
            }
            
            // Only target smaller snakes
            if ($snake->getLength() < $you->getLength()) {
                $targets[] = [
                    'head' => $snake->getHead(),
                    'length' => $snake->getLength(),
                    'distance' => $head->distanceTo($snake->getHead())
                ];
            }
        }
        
        // Sort targets by distance (closest first)
        usort($targets, function($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });
        
        // Try to path to closest smaller snake
        if (!empty($targets)) {
            $target = $targets[0]['head'];
            $path = $this->pathFinder->findPath($head, $target, $board, false);
            
            // If path found and it has at least one step
            if ($path !== null && count($path) > 1) {
                $nextStep = $path[1]; // First step is current position
                $direction = $this->pathFinder->getDirection($head, $nextStep);
                
                // Only follow path if the direction is in possible moves
                if (in_array($direction, $possibleMoves)) {
                    return $direction;
                }
            }
        }
        
        // If no target or no path, fall back to survival strategy
        $spaceByMove = $this->floodFill->evaluateMoveSpaces($head, $possibleMoves, $board);
        
        // Find move with most space
        $bestMove = null;
        $mostSpace = -1;
        
        foreach ($spaceByMove as $move => $space) {
            if ($space > $mostSpace) {
                $mostSpace = $space;
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
        
        // Base aggressive score
        $baseScore = 0.3;
        
        // Increase score if we're one of the longest snakes
        $longerSnakeCount = 0;
        $totalSnakes = count($board->getSnakes());
        
        foreach ($board->getSnakes() as $snake) {
            if ($snake->getId() !== $you->getId() && $snake->getLength() > $you->getLength()) {
                $longerSnakeCount++;
            }
        }
        
        // If we're the longest or second longest, be more aggressive
        $lengthAdvantageScore = ($totalSnakes - $longerSnakeCount) / $totalSnakes * 0.4;
        
        // Increase score if health is good
        $healthScore = $you->getHealth() / 100 * 0.3;
        
        return $baseScore + $lengthAdvantageScore + $healthScore;
    }
}
