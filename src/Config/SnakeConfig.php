<?php

namespace BattleSnake\Config;

/**
 * Configuration parameters for the Battlesnake
 */
class SnakeConfig
{
    // Snake appearance
    public const SNAKE_COLOR = '#5ae645';
    public const SNAKE_HEAD = 'rbc-bowler';
    public const SNAKE_TAIL = 'replit-notmark';
    
    // Strategy weights
    public const FOOD_STRATEGY_WEIGHT = 2;
    public const SURVIVAL_STRATEGY_WEIGHT = 0.8;
    public const AGGRESSIVE_STRATEGY_WEIGHT = 0.5;
    
    // Health thresholds
    public const LOW_HEALTH_THRESHOLD = 30;
    public const CRITICAL_HEALTH_THRESHOLD = 15;
    
    // Pathfinding parameters
    public const MAX_PATHFINDING_DEPTH = 100;
    public const HAZARD_WEIGHT = 5;
    
    // Performance settings
    public const MOVE_TIMEOUT_BUFFER_MS = 50;
    
    // Logging
    public const ENABLE_LOGGING = true;
    public const LOG_LEVEL = 'info'; // debug, info, warning, error
}
