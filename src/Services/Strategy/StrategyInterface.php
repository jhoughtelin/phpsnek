<?php

namespace BattleSnake\Services\Strategy;

use BattleSnake\Models\GameState;

/**
 * Interface for strategy implementations
 */
interface StrategyInterface
{
    /**
     * Determine the next move for the snake
     * 
     * @param GameState $gameState Current game state
     * @return string Direction ('up', 'down', 'left', 'right')
     */
    public function determineMove(GameState $gameState): string;
    
    /**
     * Calculate a score for this strategy based on current game state
     * Used by the strategy selector to determine which strategy to use
     * 
     * @param GameState $gameState Current game state
     * @return float Score indicating how appropriate this strategy is (higher is better)
     */
    public function calculateScore(GameState $gameState): float;
}
