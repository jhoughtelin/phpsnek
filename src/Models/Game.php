<?php

namespace BattleSnake\Models;

/**
 * Represents a game object from Battlesnake API
 */
class Game
{
    private string $id;
    private Ruleset $ruleset;
    private int $timeout;

    /**
     * Create a new Game from request data
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->ruleset = new Ruleset($data['ruleset']);
        $this->timeout = $data['timeout'];
    }

    /**
     * Get the game ID
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the ruleset
     */
    public function getRuleset(): Ruleset
    {
        return $this->ruleset;
    }

    /**
     * Get the timeout in milliseconds
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }
}
