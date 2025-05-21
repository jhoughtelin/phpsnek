<?php

namespace BattleSnake\Services\Strategy;

use BattleSnake\Config\SnakeConfig;
use BattleSnake\Models\GameState;
use BattleSnake\Services\PathFinding\PathFinderInterface;
use BattleSnake\Services\SpaceAnalysis\FloodFill;
use BattleSnake\Utils\Logger;

/**
 * Domination strategy - combines food seeking and aggressive behaviors
 * to become the largest snake while eliminating smaller ones
 */
class DominationStrategy implements StrategyInterface
{
    private PathFinderInterface $pathFinder;
    private FloodFill $floodFill;
    
    // Safety thresholds
    private const SAFE_DISTANCE_FROM_LARGER_SNAKE = 3;
    private const MIN_SAFE_SPACE = 6;
    private const HEAD_ON_COLLISION_DISTANCE = 2;
    
    // Health thresholds
    private const LOW_HEALTH_THRESHOLD = 50;
    private const CRITICAL_HEALTH_THRESHOLD = 25;
    
    // Hunting thresholds
    private const HUNTING_HEALTH_THRESHOLD = 60;
    private const LENGTH_ADVANTAGE_THRESHOLD = 2;
    
    // Food seeking thresholds
    private const FOOD_SEEKING_HEALTH = 85;
    private const FOOD_DISTANCE_WEIGHT = 0.7;
    private const FOOD_SAFETY_WEIGHT = 0.3;
    
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
        
        if (empty($possibleMoves)) {
            return 'up';
        }
        
        // Analyze all snakes
        $threats = [];
        $targets = [];
        foreach ($board->getSnakes() as $snake) {
            if ($snake->getId() === $you->getId()) {
                continue;
            }
            
            $snakeHead = $snake->getHead();
            $distance = $head->distanceTo($snakeHead);
            
            if ($snake->getLength() >= $you->getLength()) {
                $threats[] = [
                    'snake' => $snake,
                    'head' => $snakeHead,
                    'length' => $snake->getLength(),
                    'distance' => $distance
                ];
            } else {
                // Calculate how much smaller they are
                $lengthAdvantage = $you->getLength() - $snake->getLength();
                $escapeRoutes = count($snake->getPossibleMoves($board));
                $spaceAround = $this->floodFill->calculateAvailableSpace($snakeHead, $board);
                
                $targets[] = [
                    'snake' => $snake,
                    'head' => $snakeHead,
                    'length' => $snake->getLength(),
                    'distance' => $distance,
                    'advantage' => $lengthAdvantage,
                    'escapeRoutes' => $escapeRoutes,
                    'spaceAround' => $spaceAround
                ];
            }
        }
        
        // Calculate safety scores for possible moves
        $moveScores = $this->evaluateMoves($head, $possibleMoves, $threats, $board);
        
        // Check if we should hunt
        $canHunt = $you->getHealth() >= self::HUNTING_HEALTH_THRESHOLD;
        if ($canHunt && !empty($targets)) {
            // Sort targets by priority (closer, more trapped targets first)
            usort($targets, function($a, $b) {
                // Heavily weight targets with few escape routes
                $aScore = $a['escapeRoutes'] * 10 + $a['distance'];
                $bScore = $b['escapeRoutes'] * 10 + $b['distance'];
                return $aScore <=> $bScore;
            });
            
            foreach ($targets as $target) {
                // Only hunt if we have a significant advantage
                if ($target['advantage'] >= self::LENGTH_ADVANTAGE_THRESHOLD) {
                    $huntingMoves = $this->findHuntingMoves($head, $target, $board);
                    foreach ($huntingMoves as $move) {
                        if (in_array($move, $possibleMoves) && $moveScores[$move] >= self::MIN_SAFE_SPACE) {
                            return $move;
                        }
                    }
                }
            }
        }
        
        // If not hunting or no good hunting moves, consider food
        $needsFood = $you->getHealth() <= self::LOW_HEALTH_THRESHOLD;
        $desperate = $you->getHealth() <= self::CRITICAL_HEALTH_THRESHOLD;
        
        if ($needsFood || $this->shouldSeekFood($you, $board)) {
            $foodMove = $this->findBestFoodMove($head, $board, $threats, $moveScores, $desperate);
            if ($foodMove !== null) {
                return $foodMove;
            }
        }
        
        // Default to the safest move
        return $this->findSafestMove($moveScores, $possibleMoves);
    }
    
    /**
     * Evaluate safety and strategic value of possible moves
     */
    private function evaluateMoves($head, array $possibleMoves, array $threats, $board): array
    {
        $moveScores = [];
        foreach ($possibleMoves as $move) {
            $nextPos = $head->move($move);
            $space = $this->floodFill->calculateAvailableSpace($nextPos, $board);
            $score = $space;
            
            // Check for dangerous head-on collisions
            foreach ($threats as $threat) {
                $snakeHead = $threat['head'];
                $currentDistance = $head->distanceTo($snakeHead);
                $newDistance = $nextPos->distanceTo($snakeHead);
                
                // Severe penalty for potential head-on collisions with larger snakes
                if ($newDistance <= self::HEAD_ON_COLLISION_DISTANCE) {
                    $score -= 100;
                } else if ($newDistance <= self::SAFE_DISTANCE_FROM_LARGER_SNAKE) {
                    $score -= (self::SAFE_DISTANCE_FROM_LARGER_SNAKE - $newDistance + 1) * 15;
                    
                    // Extra penalty for moving closer to threats
                    if ($newDistance < $currentDistance) {
                        $score -= 20;
                    }
                }
            }
            
            $moveScores[$move] = $score;
        }
        
        return $moveScores;
    }
    
    /**
     * Find moves that help hunt/trap a target snake
     */
    private function findHuntingMoves($head, array $target, $board): array
    {
        $huntingMoves = [];
        $targetHead = $target['head'];
        
        // Try to cut off escape routes
        $directions = ['up', 'down', 'left', 'right'];
        foreach ($directions as $dir) {
            $escapePos = $targetHead->move($dir);
            if ($board->isWithinBounds($escapePos)) {
                // If we can reach this position, it's a potential hunting move
                $path = $this->pathFinder->findPath($head, $escapePos, $board);
                if ($path !== null && count($path) > 1) {
                    $huntingMoves[] = $this->pathFinder->getDirection($head, $path[1]);
                }
            }
        }
        
        // Also consider direct confrontation if we're much larger
        if ($target['advantage'] >= self::LENGTH_ADVANTAGE_THRESHOLD + 1) {
            $path = $this->pathFinder->findPath($head, $targetHead, $board);
            if ($path !== null && count($path) > 1) {
                $huntingMoves[] = $this->pathFinder->getDirection($head, $path[1]);
            }
        }
        
        return array_unique($huntingMoves);
    }
    
    /**
     * Determine if snake should seek food based on game state
     */
    private function shouldSeekFood($you, $board): bool
    {
        // More aggressive food seeking conditions
        
        // Always seek food if health is below threshold
        if ($you->getHealth() < self::FOOD_SEEKING_HEALTH) {
            return true;
        }
        
        // Always seek food if we're not significantly longer than others
        $maxOtherLength = 0;
        foreach ($board->getSnakes() as $snake) {
            if ($snake->getId() !== $you->getId()) {
                $maxOtherLength = max($maxOtherLength, $snake->getLength());
            }
        }
        
        // Seek food unless we're at least 2 longer than any other snake
        if ($you->getLength() < $maxOtherLength + 2) {
            return true;
        }
        
        // Count nearby food
        $nearbyFood = 0;
        foreach ($board->getFood() as $food) {
            if ($you->getHead()->distanceTo($food) <= 3) {
                $nearbyFood++;
            }
        }
        
        // If there's food very close, consider getting it
        return $nearbyFood > 0;
    }
    
    /**
     * Find the best move to get food
     */
    private function findBestFoodMove($head, $board, array $threats, array $moveScores, bool $desperate): ?string
    {
        $foodTargets = [];
        $maxDistance = $board->getWidth() + $board->getHeight();
        
        foreach ($board->getFood() as $food) {
            $distance = $head->distanceTo($food);
            $isSafe = true;
            $dangerLevel = 0;
            $competitionLevel = 0;
            
            // Evaluate food safety and competition
            foreach ($threats as $threat) {
                $snakeDistance = $threat['head']->distanceTo($food);
                if ($snakeDistance <= $distance) {
                    $isSafe = false;
                    $dangerLevel++;
                    $competitionLevel++;
                }
            }
            
            // Calculate a weighted score for this food
            $distanceScore = 1 - ($distance / $maxDistance);
            $safetyScore = $isSafe ? 1 : (1 / (1 + $dangerLevel));
            $competitionScore = 1 / (1 + $competitionLevel);
            
            $totalScore = ($distanceScore * self::FOOD_DISTANCE_WEIGHT) +
                         ($safetyScore * self::FOOD_SAFETY_WEIGHT) +
                         ($competitionScore * 0.2);
            
            $foodTargets[] = [
                'food' => $food,
                'distance' => $distance,
                'isSafe' => $isSafe,
                'dangerLevel' => $dangerLevel,
                'score' => $totalScore
            ];
        }
        
        // Sort food by total score (higher is better)
        usort($foodTargets, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // Try paths to food, accepting more risk for high-scoring targets
        foreach ($foodTargets as $target) {
            // Skip only extremely dangerous food unless desperate
            if (!$desperate && $target['dangerLevel'] >= 3) {
                continue;
            }
            
            $path = $this->pathFinder->findPath($head, $target['food'], $board);
            if ($path !== null && count($path) > 1) {
                $direction = $this->pathFinder->getDirection($head, $path[1]);
                if (isset($moveScores[$direction])) {
                    // Accept more risk for high-scoring food
                    $minSafetyScore = self::MIN_SAFE_SPACE * (1 - ($target['score'] * 0.3));
                    if ($desperate || $moveScores[$direction] >= $minSafetyScore) {
                        return $direction;
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * Find the safest available move
     */
    private function findSafestMove(array $moveScores, array $possibleMoves): string
    {
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
     * Calculate strategy score based on current game state
     */
    public function calculateScore(GameState $gameState): float
    {
        $you = $gameState->getYou();
        $board = $gameState->getBoard();
        
        // Base score
        $baseScore = 0.7;
        
        // Count threats and potential targets
        $threats = 0;
        $targets = 0;
        $isLongest = true;
        $nearbyFood = 0;
        
        foreach ($board->getSnakes() as $snake) {
            if ($snake->getId() !== $you->getId()) {
                if ($snake->getLength() >= $you->getLength()) {
                    $threats++;
                    $isLongest = false;
                } else {
                    $targets++;
                }
            }
        }
        
        // Count food within close range
        foreach ($board->getFood() as $food) {
            if ($you->getHead()->distanceTo($food) <= 3) {
                $nearbyFood++;
            }
        }
        
        // Adjust score based on game state
        $healthScore = ($you->getHealth() < self::LOW_HEALTH_THRESHOLD) ? -0.2 : 0;
        $lengthBonus = $isLongest ? 0.2 : 0;
        $targetBonus = min(0.3, $targets * 0.1);
        $threatPenalty = min(0.4, $threats * 0.2);
        $foodBonus = min(0.3, $nearbyFood * 0.1);
        
        return $baseScore + $healthScore + $lengthBonus + $targetBonus - $threatPenalty + $foodBonus;
    }
} 