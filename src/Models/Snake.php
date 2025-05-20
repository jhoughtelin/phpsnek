<?php

namespace BattleSnake\Models;

/**
 * Represents a snake in the game
 */
class Snake
{
    private string $id;
    private string $name;
    private int $health;
    private array $body;
    private Coord $head;
    private int $length;
    private string $shout;
    private string $squad;
    private array $customizations;

    /**
     * Create a new Snake from request data
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->health = $data['health'];
        $this->body = array_map(function ($item) {
            return new Coord($item);
        }, $data['body']);
        $this->head = new Coord($data['head']);
        $this->length = $data['length'];
        $this->shout = $data['shout'] ?? '';
        $this->squad = $data['squad'] ?? '';
        $this->customizations = $data['customizations'] ?? [];
    }

    /**
     * Get the snake ID
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the snake name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the snake health
     */
    public function getHealth(): int
    {
        return $this->health;
    }

    /**
     * Get the snake body coordinates
     * 
     * @return Coord[]
     */
    public function getBody(): array
    {
        return $this->body;
    }

    /**
     * Get the snake head coordinate
     */
    public function getHead(): Coord
    {
        return $this->head;
    }

    /**
     * Get the snake length
     */
    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * Get the snake shout
     */
    public function getShout(): string
    {
        return $this->shout;
    }

    /**
     * Get the snake squad
     */
    public function getSquad(): string
    {
        return $this->squad;
    }

    /**
     * Get the snake customizations
     */
    public function getCustomizations(): array
    {
        return $this->customizations;
    }

    /**
     * Check if a coordinate is part of this snake's body
     */
    public function containsPoint(Coord $coord): bool
    {
        foreach ($this->body as $bodyPart) {
            if ($bodyPart->equals($coord)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get possible moves for the snake
     * 
     * @return array Array of possible directions ('up', 'down', 'left', 'right')
     */
    public function getPossibleMoves(Board $board): array
    {
        $possibleMoves = [];
        $directions = ['up', 'down', 'left', 'right'];
        
        foreach ($directions as $direction) {
            $newHead = $this->head->move($direction);
            
            // Check if the move is within bounds
            if (!$board->isWithinBounds($newHead)) {
                continue;
            }
            
            // Check if the move would hit a snake (excluding the tail if not growing)
            $willGrow = $board->hasFood($this->head);
            $hitSnake = false;
            
            foreach ($board->getSnakes() as $snake) {
                foreach ($snake->getBody() as $i => $bodyPart) {
                    // Skip checking the tail if the snake won't grow
                    if (!$willGrow && $i === count($snake->getBody()) - 1) {
                        continue;
                    }
                    
                    if ($newHead->equals($bodyPart)) {
                        $hitSnake = true;
                        break 2;
                    }
                }
            }
            
            if (!$hitSnake) {
                $possibleMoves[] = $direction;
            }
        }
        
        return $possibleMoves;
    }
}
