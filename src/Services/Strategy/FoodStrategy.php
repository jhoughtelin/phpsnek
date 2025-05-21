<?php

namespace BattleSnake\Services\Strategy;

use BattleSnake\Config\SnakeConfig;
use BattleSnake\Models\GameState;
use BattleSnake\Services\PathFinding\PathFinderInterface;
use BattleSnake\Services\SpaceAnalysis\FloodFill;
use BattleSnake\Utils\Logger;

/**
 * Food seeking strategy - prioritizes finding and eating food while avoiding dangerous snakes
 */
class FoodStrategy implements StrategyInterface
{
    private PathFinderInterface $pathFinder;
    private FloodFill $floodFill;
    
    // Constants for safety thresholds
    private const SAFE_DISTANCE_FROM_LARGER_SNAKE = 3;
    private const MIN_SAFE_SPACE = 8;
    private const LOW_HEALTH_THRESHOLD = 30;
    private const CRITICAL_HEALTH_THRESHOLD = 15;
    private const HEAD_ON_COLLISION_DISTANCE = 2; // Distance to check for potential head-on collisions
    private const EXTREME_DANGER_PENALTY = 100; // High penalty for moves that could cause head-on collisions
    
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
        
        // Identify dangerous snakes (larger or equal size)
        $dangerousSnakes = [];
        foreach ($board->getSnakes() as $snake) {
            if ($snake->getId() !== $you->getId() && $snake->getLength() >= $you->getLength()) {
                $dangerousSnakes[] = $snake;
            }
        }
        
        // Calculate safety scores for each possible move
        $safetyScores = [];
        foreach ($possibleMoves as $move) {
            $nextPos = $head->move($move);
            $space = $this->floodFill->calculateAvailableSpace($nextPos, $board);
            $safetyScore = $space;
            
            // Check for potential head-on collisions with larger snakes
            foreach ($dangerousSnakes as $snake) {
                $snakeHead = $snake->getHead();
                $currentDistance = $head->distanceTo($snakeHead);
                $newDistance = $nextPos->distanceTo($snakeHead);
                
                // Extreme penalty for moves that could lead to head-on collision
                if ($newDistance <= self::HEAD_ON_COLLISION_DISTANCE) {
                    // Calculate possible next positions for the dangerous snake
                    $dangerousSnakeMoves = $snake->getPossibleMoves($board);
                    foreach ($dangerousSnakeMoves as $enemyMove) {
                        $enemyNextPos = $snakeHead->move($enemyMove);
                        if ($nextPos->distanceTo($enemyNextPos) <= 1) {
                            // This move could result in head-on collision
                            $safetyScore -= self::EXTREME_DANGER_PENALTY;
                            break;
                        }
                    }
                }
                
                // Additional penalty for getting closer to dangerous snakes
                if ($newDistance <= self::SAFE_DISTANCE_FROM_LARGER_SNAKE) {
                    $safetyScore -= (self::SAFE_DISTANCE_FROM_LARGER_SNAKE - $newDistance + 1) * 20;
                    
                    // Extra penalty if we're moving closer rather than further
                    if ($newDistance < $currentDistance) {
                        $safetyScore -= 30;
                    }
                }
            }
            
            $safetyScores[$move] = $safetyScore;
        }
        
        // If health is low, try to find safe path to food
        $needsFood = $you->getHealth() <= self::LOW_HEALTH_THRESHOLD;
        $desperate = $you->getHealth() <= self::CRITICAL_HEALTH_THRESHOLD;
        
        if ($needsFood) {
            // Find and sort food by distance and safety
            $foodTargets = [];
            foreach ($board->getFood() as $food) {
                $distance = $head->distanceTo($food);
                $isSafe = true;
                $dangerLevel = 0;
                
                // Check if food is too close to dangerous snakes
                foreach ($dangerousSnakes as $snake) {
                    $snakeDistance = $snake->getHead()->distanceTo($food);
                    // Consider snake's possible moves to the food
                    if ($snakeDistance <= $distance) {
                        $isSafe = false;
                        $dangerLevel++;
                    }
                    // Extra danger if snake is between us and food
                    if ($this->isSnakeBetween($head, $food, $snake->getHead())) {
                        $dangerLevel += 2;
                    }
                }
                
                $foodTargets[] = [
                    'food' => $food,
                    'distance' => $distance,
                    'isSafe' => $isSafe,
                    'dangerLevel' => $dangerLevel
                ];
            }
            
            // Sort food targets by safety, danger level, and distance
            usort($foodTargets, function($a, $b) {
                // First priority: safety
                if ($a['isSafe'] !== $b['isSafe']) {
                    return $b['isSafe'] <=> $a['isSafe'];
                }
                // Second priority: danger level
                if ($a['dangerLevel'] !== $b['dangerLevel']) {
                    return $a['dangerLevel'] <=> $b['dangerLevel'];
                }
                // Last priority: distance
                return $a['distance'] <=> $b['distance'];
            });
            
            // Try paths to food targets
            foreach ($foodTargets as $target) {
                // Skip unsafe food unless desperate
                if (!$desperate && !$target['isSafe']) {
                    continue;
                }
                
                // Skip extremely dangerous food even when desperate
                if ($target['dangerLevel'] >= 3) {
                    continue;
                }
                
                $path = $this->pathFinder->findPath($head, $target['food'], $board);
                
                if ($path !== null && count($path) > 1) {
                    $nextStep = $path[1];
                    $direction = $this->pathFinder->getDirection($head, $nextStep);
                    
                    // Only take the move if it's safe enough
                    if (in_array($direction, $possibleMoves)) {
                        if ($desperate) {
                            // When desperate, just avoid immediate death
                            if ($safetyScores[$direction] > -self::EXTREME_DANGER_PENALTY) {
                                return $direction;
                            }
                        } else {
                            // Otherwise require good safety score
                            if ($safetyScores[$direction] >= self::MIN_SAFE_SPACE) {
                                return $direction;
                            }
                        }
                    }
                }
            }
        }
        
        // If no safe path to food or health is sufficient, choose safest move
        $bestMove = null;
        $bestScore = -PHP_INT_MAX;
        
        foreach ($safetyScores as $move => $score) {
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMove = $move;
            }
        }
        
        return $bestMove ?? $possibleMoves[0];
    }
    
    /**
     * Check if a snake's head is roughly between two points
     * 
     * @param Coord $start Starting point
     * @param Coord $end Ending point
     * @param Coord $snakeHead Snake head position
     * @return bool True if snake is between points
     */
    private function isSnakeBetween($start, $end, $snakeHead): bool
    {
        $distanceStartToEnd = $start->distanceTo($end);
        $distanceStartToSnake = $start->distanceTo($snakeHead);
        $distanceSnakeToEnd = $snakeHead->distanceTo($end);
        
        // Allow some tolerance in the calculation
        return ($distanceStartToSnake + $distanceSnakeToEnd) <= ($distanceStartToEnd + 2);
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
        $board = $gameState->getBoard();
        
        // Score based on health - lower health means higher score for food strategy
        $healthScore = (100 - $health) / 100;
        
        // Early game bonus
        $turnBonus = max(0, (50 - $gameState->getTurn()) / 50);
        
        // Length penalty - longer snakes need less food
        $lengthPenalty = 0;
        
        // Reduce score if surrounded by dangerous snakes
        $dangerPenalty = 0;
        foreach ($board->getSnakes() as $snake) {
            if ($snake->getId() !== $you->getId() && $snake->getLength() >= $you->getLength()) {
                $distance = $snake->getHead()->distanceTo($you->getHead());
                if ($distance <= self::HEAD_ON_COLLISION_DISTANCE) {
                    // Severe penalty for potential head-on collisions
                    $dangerPenalty += 0.5;
                } else if ($distance <= self::SAFE_DISTANCE_FROM_LARGER_SNAKE) {
                    $dangerPenalty += 0.2;
                }
            }
        }
        
        $score = ($healthScore * SnakeConfig::FOOD_STRATEGY_WEIGHT) + 
                 ($turnBonus * 0.2) - 
                 ($lengthPenalty * 0.1) - 
                 min(0.9, $dangerPenalty); // Cap danger penalty but make it more severe
                 
        Logger::getLogger()->info('Food strategy score: ' . $score);
        return $score;
    }
}
