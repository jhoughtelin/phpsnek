<?php

namespace BattleSnake\Models;

/**
 * Represents a board object from Battlesnake API
 */
class Board
{
    private int $height;
    private int $width;
    private array $food;
    private array $hazards;
    private array $snakes;

    /**
     * Create a new Board from request data
     */
    public function __construct(array $data)
    {
        $this->height = $data['height'];
        $this->width = $data['width'];
        $this->food = array_map(function ($item) {
            return new Coord($item);
        }, $data['food']);
        $this->hazards = array_map(function ($item) {
            return new Coord($item);
        }, $data['hazards']);
        $this->snakes = array_map(function ($item) {
            return new Snake($item);
        }, $data['snakes']);
    }

    /**
     * Get the board height
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * Get the board width
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * Get the food coordinates
     * 
     * @return Coord[]
     */
    public function getFood(): array
    {
        return $this->food;
    }

    /**
     * Get the hazard coordinates
     * 
     * @return Coord[]
     */
    public function getHazards(): array
    {
        return $this->hazards;
    }

    /**
     * Get the snakes on the board
     * 
     * @return Snake[]
     */
    public function getSnakes(): array
    {
        return $this->snakes;
    }

    /**
     * Check if a coordinate is within the board boundaries
     */
    public function isWithinBounds(Coord $coord): bool
    {
        return $coord->getX() >= 0 && 
               $coord->getX() < $this->width && 
               $coord->getY() >= 0 && 
               $coord->getY() < $this->height;
    }

    /**
     * Check if a coordinate contains food
     */
    public function hasFood(Coord $coord): bool
    {
        foreach ($this->food as $food) {
            if ($food->equals($coord)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if a coordinate contains a hazard
     */
    public function hasHazard(Coord $coord): bool
    {
        foreach ($this->hazards as $hazard) {
            if ($hazard->equals($coord)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if a coordinate contains a snake body part
     */
    public function hasSnake(Coord $coord): bool
    {
        foreach ($this->snakes as $snake) {
            foreach ($snake->getBody() as $body) {
                if ($body->equals($coord)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get a 2D grid representation of the board
     * 
     * Returns a 2D array where:
     * - 0 = empty space
     * - 1 = food
     * - 2 = hazard
     * - 3 = snake body
     * - 4 = snake head
     */
    public function getGrid(): array
    {
        $grid = [];
        
        // Initialize empty grid
        for ($y = 0; $y < $this->height; $y++) {
            $grid[$y] = [];
            for ($x = 0; $x < $this->width; $x++) {
                $grid[$y][$x] = 0;
            }
        }
        
        // Add food
        foreach ($this->food as $food) {
            $grid[$food->getY()][$food->getX()] = 1;
        }
        
        // Add hazards
        foreach ($this->hazards as $hazard) {
            $grid[$hazard->getY()][$hazard->getX()] = 2;
        }
        
        // Add snakes
        foreach ($this->snakes as $snake) {
            // Add head
            $head = $snake->getHead();
            $grid[$head->getY()][$head->getX()] = 4;
            
            // Add body
            $body = $snake->getBody();
            for ($i = 1; $i < count($body); $i++) {
                $part = $body[$i];
                $grid[$part->getY()][$part->getX()] = 3;
            }
        }
        
        return $grid;
    }
}
