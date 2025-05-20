<?php

namespace BattleSnake\Models;

/**
 * Represents a ruleset object from Battlesnake API
 */
class Ruleset
{
    private string $name;
    private array $settings;

    /**
     * Create a new Ruleset from request data
     */
    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->settings = $data['settings'] ?? [];
    }

    /**
     * Get the ruleset name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the ruleset settings
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * Get a specific setting value
     */
    public function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Get the food spawn chance
     */
    public function getFoodSpawnChance(): int
    {
        return $this->getSetting('foodSpawnChance', 15);
    }

    /**
     * Get the minimum food on board
     */
    public function getMinimumFood(): int
    {
        return $this->getSetting('minimumFood', 1);
    }

    /**
     * Get the hazard damage per turn
     */
    public function getHazardDamagePerTurn(): int
    {
        return $this->getSetting('hazardDamagePerTurn', 0);
    }
}
