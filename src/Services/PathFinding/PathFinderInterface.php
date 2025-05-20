<?php

namespace BattleSnake\Services\PathFinding;

use BattleSnake\Models\Coord;
use BattleSnake\Models\Board;

/**
 * Interface for pathfinding algorithms
 */
interface PathFinderInterface
{
    /**
     * Find a path from start to end
     * 
     * @param Coord $start Starting coordinate
     * @param Coord $end Target coordinate
     * @param Board $board Game board
     * @param bool $avoidSnakes Whether to avoid snake bodies
     * @param bool $avoidHazards Whether to avoid hazard squares
     * @return array|null Array of Coord objects representing the path, or null if no path exists
     */
    public function findPath(Coord $start, Coord $end, Board $board, bool $avoidSnakes = true, bool $avoidHazards = true): ?array;
    
    /**
     * Get the direction to move based on the next step in the path
     * 
     * @param Coord $current Current position
     * @param Coord $next Next position
     * @return string Direction ('up', 'down', 'left', 'right')
     */
    public function getDirection(Coord $current, Coord $next): string;
}
