<?php

namespace BattleSnake\Utils;

/**
 * Timer utility for performance monitoring
 */
class Timer
{
    private static float $startTime;
    private static array $checkpoints = [];
    
    /**
     * Start the timer
     */
    public static function start(): void
    {
        self::$startTime = microtime(true);
        self::$checkpoints = [];
    }
    
    /**
     * Record a checkpoint
     */
    public static function checkpoint(string $name): void
    {
        self::$checkpoints[$name] = microtime(true) - self::$startTime;
    }
    
    /**
     * Get elapsed time since start in milliseconds
     */
    public static function getElapsedMs(): float
    {
        return (microtime(true) - self::$startTime) * 1000;
    }
    
    /**
     * Get all checkpoints
     */
    public static function getCheckpoints(): array
    {
        return self::$checkpoints;
    }
    
    /**
     * Check if we're approaching the timeout
     */
    public static function isApproachingTimeout(int $timeoutMs, int $bufferMs = 50): bool
    {
        return self::getElapsedMs() > ($timeoutMs - $bufferMs);
    }
}
