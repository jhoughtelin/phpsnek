<?php

namespace BattleSnake\Models;

/**
 * Represents a coordinate in the game board
 */
class Coord
{
    private int $x;
    private int $y;

    /**
     * Create a new Coord from request data
     */
    public function __construct(array $data)
    {
        $this->x = $data['x'];
        $this->y = $data['y'];
    }

    /**
     * Get the X coordinate
     */
    public function getX(): int
    {
        return $this->x;
    }

    /**
     * Get the Y coordinate
     */
    public function getY(): int
    {
        return $this->y;
    }

    /**
     * Check if this coordinate equals another
     */
    public function equals(Coord $other): bool
    {
        return $this->x === $other->x && $this->y === $other->y;
    }

    /**
     * Get a string representation of the coordinate
     */
    public function toString(): string
    {
        return "{$this->x},{$this->y}";
    }

    /**
     * Get a new coordinate by moving in a direction
     */
    public function move(string $direction): Coord
    {
        $newCoord = [
            'x' => $this->x,
            'y' => $this->y
        ];
        
        switch ($direction) {
            case 'up':
                $newCoord['y'] += 1;
                break;
            case 'down':
                $newCoord['y'] -= 1;
                break;
            case 'left':
                $newCoord['x'] -= 1;
                break;
            case 'right':
                $newCoord['x'] += 1;
                break;
        }
        
        return new Coord($newCoord);
    }

    /**
     * Calculate Manhattan distance to another coordinate
     */
    public function distanceTo(Coord $other): int
    {
        return abs($this->x - $other->x) + abs($this->y - $other->y);
    }
}
