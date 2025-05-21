<?php

namespace BattleSnake\Services\SpaceAnalysis;

use BattleSnake\Models\Coord;
use BattleSnake\Models\Board;

/**
 * Flood fill implementation for space analysis
 */
class FloodFill
{
    /**
     * Perform flood fill from a starting point to determine available space
     * 
     * @param Coord $start Starting coordinate
     * @param Board $board Game board
     * @param int $maxDepth Maximum depth to search (0 for unlimited)
     * @return int Number of accessible squares
     */
    public function countAccessibleSquares(Coord $start, Board $board, int $maxDepth = 0): int
    {
        $visited = [];
        $queue = [$start];
        $count = 0;
        $depth = 0;
        
        while (!empty($queue) && ($maxDepth === 0 || $depth < $maxDepth)) {
            $levelSize = count($queue);
            
            for ($i = 0; $i < $levelSize; $i++) {
                $current = array_shift($queue);
                $currentKey = $current->toString();
                
                // Skip if already visited
                if (isset($visited[$currentKey])) {
                    continue;
                }
                
                // Mark as visited and increment count
                $visited[$currentKey] = true;
                $count++;
                
                // Check all neighbors
                $directions = ['up', 'down', 'left', 'right'];
                foreach ($directions as $direction) {
                    $neighbor = $current->move($direction);
                    $neighborKey = $neighbor->toString();
                    
                    // Skip if already visited
                    if (isset($visited[$neighborKey])) {
                        continue;
                    }
                    
                    // Skip if out of bounds
                    if (!$board->isWithinBounds($neighbor)) {
                        continue;
                    }
                    
                    // Skip if contains snake
                    if ($board->hasSnake($neighbor)) {
                        continue;
                    }
                    
                    // Add to queue
                    $queue[] = $neighbor;
                }
            }
            
            $depth++;
        }
        
        return $count;
    }
    
    /**
     * Calculate available space from a single point
     * 
     * @param Coord $position Position to calculate space from
     * @param Board $board Game board
     * @return int Number of accessible squares
     */
    public function calculateAvailableSpace(Coord $position, Board $board): int
    {
        // Simply use countAccessibleSquares with no depth limit
        return $this->countAccessibleSquares($position, $board, 0);
    }
    
    /**
     * Evaluate the available space for each possible move
     * 
     * @param Coord $head Snake head position
     * @param array $possibleMoves Array of possible directions
     * @param Board $board Game board
     * @return array Associative array of direction => space count
     */
    public function evaluateMoveSpaces(Coord $head, array $possibleMoves, Board $board): array
    {
        $result = [];
        
        foreach ($possibleMoves as $direction) {
            $newHead = $head->move($direction);
            $spaceCount = $this->countAccessibleSquares($newHead, $board);
            $result[$direction] = $spaceCount;
        }
        
        return $result;
    }
}
