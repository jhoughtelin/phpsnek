<?php

namespace BattleSnake\Services\Strategy;

use BattleSnake\Models\GameState;
use BattleSnake\Services\PathFinding\PathFinderInterface;
use BattleSnake\Services\SpaceAnalysis\FloodFill;

/**
 * Aggressive strategy - prioritizes attacking and cornering other snakes
 * while maintaining tactical awareness of threats
 */
class AggressiveStrategy implements StrategyInterface
{
    private PathFinderInterface $pathFinder;
    private FloodFill $floodFill;
    
    // Minimum safe space threshold
    private const MIN_SAFE_SPACE = 8;
    // Distance to consider a snake nearby
    private const NEARBY_DISTANCE = 2;
    // Minimum health to maintain aggressive behavior
    private const MIN_AGGRESSIVE_HEALTH = 50;
    
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

        // First, evaluate threats from longer snakes
        $threats = [];
        $targets = [];
        foreach ($board->getSnakes() as $snake) {
            // Skip our own snake
            if ($snake->getId() === $you->getId()) {
                continue;
            }
            
            $snakeHead = $snake->getHead();
            $distance = $head->distanceTo($snakeHead);
            
            if ($snake->getLength() >= $you->getLength()) {
                // This is a threat
                $threats[] = [
                    'head' => $snakeHead,
                    'length' => $snake->getLength(),
                    'distance' => $distance
                ];
            } else {
                // This is a potential target
                $targetEscapeRoutes = count($snake->getPossibleMoves($board));
                $spaceAroundTarget = $this->floodFill->calculateAvailableSpace($snakeHead, $board);
                
                $targets[] = [
                    'head' => $snakeHead,
                    'length' => $snake->getLength(),
                    'distance' => $distance,
                    'escapeRoutes' => $targetEscapeRoutes,
                    'spaceAround' => $spaceAroundTarget
                ];
            }
        }

        // Calculate safety scores for each possible move
        $safetyScores = [];
        foreach ($possibleMoves as $move) {
            $nextPos = $head->move($move);
            $space = $this->floodFill->calculateAvailableSpace($nextPos, $board);
            $safetyScore = $space;
            
            // Reduce score if move brings us closer to threats
            foreach ($threats as $threat) {
                $newDistance = $nextPos->distanceTo($threat['head']);
                if ($newDistance <= self::NEARBY_DISTANCE) {
                    $safetyScore -= (self::NEARBY_DISTANCE - $newDistance + 1) * 10;
                }
            }
            
            $safetyScores[$move] = $safetyScore;
        }

        // If health is low or we're surrounded by threats, prioritize safety
        $needsSafety = $you->getHealth() < self::MIN_AGGRESSIVE_HEALTH || !empty($threats);
        
        if ($needsSafety) {
            // Find the safest move that maintains maximum space
            $bestMove = null;
            $bestScore = -1;
            
            foreach ($safetyScores as $move => $score) {
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestMove = $move;
                }
            }
            
            // Only return safe move if it provides enough space
            if ($bestScore >= self::MIN_SAFE_SPACE) {
                return $bestMove;
            }
        }

        // If we have targets and enough safety, try to attack
        if (!empty($targets)) {
            // Sort targets by priority
            usort($targets, function($a, $b) {
                // Prioritize closer targets
                $distanceComparison = $a['distance'] <=> $b['distance'];
                if ($distanceComparison !== 0) {
                    return $distanceComparison;
                }
                
                // Then consider escape routes
                return $a['escapeRoutes'] <=> $b['escapeRoutes'];
            });
            
            foreach ($targets as $target) {
                if ($target['distance'] <= self::NEARBY_DISTANCE) {
                    // Try to find a move that corners the target while maintaining safety
                    $corneringPositions = $this->findCorneringPositions($target['head'], $board);
                    foreach ($corneringPositions as $cornerPos) {
                        $path = $this->pathFinder->findPath($head, $cornerPos, $board, false);
                        
                        if ($path !== null && count($path) > 1) {
                            $nextStep = $path[1];
                            $direction = $this->pathFinder->getDirection($head, $nextStep);
                            
                            // Only take the move if it's safe
                            if (in_array($direction, $possibleMoves) && 
                                $safetyScores[$direction] >= self::MIN_SAFE_SPACE) {
                                return $direction;
                            }
                        }
                    }
                }
            }
        }
        
        // If no good attacking moves, choose the safest move
        $bestMove = null;
        $bestScore = -1;
        
        foreach ($safetyScores as $move => $score) {
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMove = $move;
            }
        }
        
        return $bestMove ?? $possibleMoves[0];
    }
    
    /**
     * Find positions that would help corner a target snake
     * 
     * @param Point $targetHead Target snake's head position
     * @param Board $board Current game board
     * @return array Array of positions that would help corner the target
     */
    private function findCorneringPositions($targetHead, $board): array
    {
        $positions = [];
        $directions = ['up', 'down', 'left', 'right'];
        
        // Look for positions that block escape routes
        foreach ($directions as $dir) {
            $pos = $targetHead->move($dir);
            if ($board->isWithinBounds($pos) && !$board->hasSnake($pos)) {
                // Check if this position would limit target's movement
                $positions[] = $pos;
                
                // Also consider positions one step further that could trap
                $nextPos = $pos->move($dir);
                if ($board->isWithinBounds($nextPos) && !$board->hasSnake($nextPos)) {
                    $positions[] = $nextPos;
                }
            }
        }
        
        return $positions;
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
        
        // Base score starts lower
        $baseScore = 0.5;
        
        // Count threats and potential targets
        $threats = 0;
        $targets = 0;
        foreach ($board->getSnakes() as $snake) {
            if ($snake->getId() !== $you->getId()) {
                if ($snake->getLength() >= $you->getLength()) {
                    $threats++;
                } else {
                    $targets++;
                }
            }
        }
        
        // Calculate length advantage score
        $totalSnakes = count($board->getSnakes());
        $lengthAdvantageScore = ($totalSnakes - $threats) / $totalSnakes * 0.4;
        
        // Reduce score significantly if there are nearby threats
        if ($threats > 0) {
            $baseScore *= 0.5;
        }
        
        // Increase score if there are potential targets and we're healthy
        if ($targets > 0 && $you->getHealth() >= self::MIN_AGGRESSIVE_HEALTH) {
            $baseScore += 0.2;
        }
        
        return $baseScore + $lengthAdvantageScore;
    }
}
