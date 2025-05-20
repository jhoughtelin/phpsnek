<?php

namespace BattleSnake\Services\Strategy;

use BattleSnake\Config\SnakeConfig;
use BattleSnake\Models\GameState;
use BattleSnake\Services\PathFinding\PathFinderInterface;
use BattleSnake\Services\SpaceAnalysis\FloodFill;
use BattleSnake\Utils\Logger;

/**
 * Food seeking strategy - prioritizes finding and eating food
 */
class FoodStrategy implements StrategyInterface
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
        
        // If health is low, prioritize finding food
        if ($you->getHealth() < 50) {
            // Find closest food
            $closestFood = null;
            $shortestDistance = PHP_INT_MAX;
            
            foreach ($board->getFood() as $food) {
                $distance = $head->distanceTo($food);
                if ($distance < $shortestDistance) {
                    $shortestDistance = $distance;
                    $closestFood = $food;
                }
            }
            
            // If food found, try to path to it
            if ($closestFood !== null) {
                $path = $this->pathFinder->findPath($head, $closestFood, $board);
                
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
        }
        
        // If no food path or health is sufficient, choose move with most space
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
        $health = $you->getHealth();
        
        // Score based on health - lower health means higher score for food strategy
        $healthScore = (100 - $health) / 100;
        
        // Early game bonus
        $turnBonus = max(0, (50 - $gameState->getTurn()) / 50);
        
        // Length penalty - longer snakes need less food
        // $lengthPenalty = min(1, $you->getLength() / 20);
        $lengthPenalty = 0;
        
        $score =  ($healthScore * SnakeConfig::FOOD_STRATEGY_WEIGHT) + ($turnBonus * 0.2) - ($lengthPenalty * 0.1);
        Logger::getLogger()->info('Food strategy score: ' . $score);
        return $score;
    }
}
