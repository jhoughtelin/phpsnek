<?php

namespace BattleSnake\Services\PathFinding;

use BattleSnake\Models\Coord;
use BattleSnake\Models\Board;

/**
 * A* Pathfinding implementation
 */
class AStarPathFinder implements PathFinderInterface
{
    /**
     * Find a path from start to end using A* algorithm
     * 
     * @param Coord $start Starting coordinate
     * @param Coord $end Target coordinate
     * @param Board $board Game board
     * @param bool $avoidSnakes Whether to avoid snake bodies
     * @param bool $avoidHazards Whether to avoid hazard squares
     * @return array|null Array of Coord objects representing the path, or null if no path exists
     */
    public function findPath(Coord $start, Coord $end, Board $board, bool $avoidSnakes = true, bool $avoidHazards = true): ?array
    {
        // Initialize open and closed sets
        $openSet = [$start->toString() => $start];
        $closedSet = [];
        
        // Initialize g and f scores
        $gScore = [$start->toString() => 0];
        $fScore = [$start->toString() => $this->heuristic($start, $end)];
        
        // Initialize came from map
        $cameFrom = [];
        
        while (!empty($openSet)) {
            // Find node with lowest f score
            $current = null;
            $lowestFScore = PHP_INT_MAX;
            
            foreach ($openSet as $node) {
                $nodeKey = $node->toString();
                if ($fScore[$nodeKey] < $lowestFScore) {
                    $lowestFScore = $fScore[$nodeKey];
                    $current = $node;
                }
            }
            
            // If we reached the end, reconstruct and return the path
            if ($current->equals($end)) {
                return $this->reconstructPath($cameFrom, $current);
            }
            
            // Remove current from open set and add to closed set
            $currentKey = $current->toString();
            unset($openSet[$currentKey]);
            $closedSet[$currentKey] = $current;
            
            // Check all neighbors
            $directions = ['up', 'down', 'left', 'right'];
            foreach ($directions as $direction) {
                $neighbor = $current->move($direction);
                $neighborKey = $neighbor->toString();
                
                // Skip if neighbor is in closed set
                if (isset($closedSet[$neighborKey])) {
                    continue;
                }
                
                // Skip if out of bounds
                if (!$board->isWithinBounds($neighbor)) {
                    continue;
                }
                
                // Skip if contains snake (and we're avoiding snakes)
                if ($avoidSnakes && $board->hasSnake($neighbor)) {
                    continue;
                }
                
                // Apply higher cost for hazards (but don't skip them completely)
                $moveCost = 1;
                if ($avoidHazards && $board->hasHazard($neighbor)) {
                    $moveCost = 5; // Higher cost for hazards
                }
                
                // Calculate tentative g score
                $tentativeGScore = $gScore[$currentKey] + $moveCost;
                
                // If neighbor not in open set, add it
                if (!isset($openSet[$neighborKey])) {
                    $openSet[$neighborKey] = $neighbor;
                } elseif ($tentativeGScore >= $gScore[$neighborKey]) {
                    // If this path is not better than previous one, skip
                    continue;
                }
                
                // This path is the best so far, record it
                $cameFrom[$neighborKey] = $current;
                $gScore[$neighborKey] = $tentativeGScore;
                $fScore[$neighborKey] = $tentativeGScore + $this->heuristic($neighbor, $end);
            }
        }
        
        // No path found
        return null;
    }
    
    /**
     * Calculate heuristic (Manhattan distance)
     */
    private function heuristic(Coord $a, Coord $b): int
    {
        return $a->distanceTo($b);
    }
    
    /**
     * Reconstruct path from came from map
     */
    private function reconstructPath(array $cameFrom, Coord $current): array
    {
        $path = [$current];
        $currentKey = $current->toString();
        
        while (isset($cameFrom[$currentKey])) {
            $current = $cameFrom[$currentKey];
            $currentKey = $current->toString();
            array_unshift($path, $current);
        }
        
        return $path;
    }
    
    /**
     * Get the direction to move based on the next step in the path
     * 
     * @param Coord $current Current position
     * @param Coord $next Next position
     * @return string Direction ('up', 'down', 'left', 'right')
     */
    public function getDirection(Coord $current, Coord $next): string
    {
        if ($next->getX() > $current->getX()) {
            return 'right';
        } elseif ($next->getX() < $current->getX()) {
            return 'left';
        } elseif ($next->getY() > $current->getY()) {
            return 'up';
        } else {
            return 'down';
        }
    }
}
