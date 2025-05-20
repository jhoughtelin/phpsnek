<?php

namespace BattleSnake\Models;

/**
 * Represents the complete game state from a Battlesnake request
 */
class GameState
{
    private Game $game;
    private int $turn;
    private Board $board;
    private Snake $you;

    /**
     * Create a new GameState from request data
     */
    public function __construct(array $data)
    {
        $this->game = new Game($data['game']);
        $this->turn = $data['turn'];
        $this->board = new Board($data['board']);
        $this->you = new Snake($data['you']);
    }

    /**
     * Get the game object
     */
    public function getGame(): Game
    {
        return $this->game;
    }

    /**
     * Get the current turn number
     */
    public function getTurn(): int
    {
        return $this->turn;
    }

    /**
     * Get the board object
     */
    public function getBoard(): Board
    {
        return $this->board;
    }

    /**
     * Get your snake object
     */
    public function getYou(): Snake
    {
        return $this->you;
    }
}
