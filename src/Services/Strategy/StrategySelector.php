<?php

namespace BattleSnake\Services\Strategy;

use BattleSnake\Models\GameState;
use BattleSnake\Services\PathFinding\AStarPathFinder;
use BattleSnake\Services\SpaceAnalysis\FloodFill;
use BattleSnake\Utils\Logger;

/**
 * Strategy selector - chooses the best strategy based on game state
 */
class StrategySelector
{
    private array $strategies = [];

    /**
     * Add a strategy to the selector
     */
    public function addStrategy(StrategyInterface $strategy): void
    {
        $this->strategies[] = $strategy;
    }

    /**
     * Select the best strategy for the current game state
     *
     * @param GameState $gameState Current game state
     * @return StrategyInterface The selected strategy
     */
    public function selectStrategy(GameState $gameState): StrategyInterface
    {
        if (empty($this->strategies)) {
//            throw new \RuntimeException('No strategies available');
            $this->strategies[] = new AggressiveStrategy(new AStarPathFinder(), new FloodFill());
            $this->strategies[] = new FoodStrategy(new AStarPathFinder(), new FloodFill());
            $this->strategies[] = new SurvivalStrategy(new FloodFill());
        }

        $bestStrategy = null;
        $bestScore = -1;

        foreach ($this->strategies as $strategy) {
            $score = $strategy->calculateScore($gameState);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestStrategy = $strategy;
            }
        }

        Logger::getLogger()->info('Selected strategy: ' . get_class($bestStrategy) . ' with score: ' . $bestScore);
        return $bestStrategy;
    }
}
