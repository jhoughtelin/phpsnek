<?php

namespace BattleSnake\Utils;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use BattleSnake\Config\SnakeConfig;

/**
 * Logger utility for Battlesnake
 */
class Logger
{
    private static ?MonologLogger $logger = null;
    
    /**
     * Get the logger instance
     */
    public static function getLogger(): MonologLogger
    {
        if (self::$logger === null) {
            self::initLogger();
        }
        
        return self::$logger;
    }
    
    /**
     * Initialize the logger
     */
    private static function initLogger(): void
    {
        // Create logger
        self::$logger = new MonologLogger('battlesnake');
        
        // Create handler
        $handler = new StreamHandler('php://stderr', self::getLogLevel());
        
        // Set formatter
        $formatter = new LineFormatter(
            "[%datetime%] %level_name%: %message% %context% %extra%\n",
            "Y-m-d H:i:s.u"
        );
        $handler->setFormatter($formatter);
        
        // Add handler to logger
        self::$logger->pushHandler($handler);
    }
    
    /**
     * Get the log level from config
     */
    private static function getLogLevel(): int
    {
        if (!SnakeConfig::ENABLE_LOGGING) {
            return MonologLogger::EMERGENCY; // Effectively disable logging
        }
        
        switch (SnakeConfig::LOG_LEVEL) {
            case 'debug':
                return MonologLogger::DEBUG;
            case 'info':
                return MonologLogger::INFO;
            case 'warning':
                return MonologLogger::WARNING;
            case 'error':
                return MonologLogger::ERROR;
            default:
                return MonologLogger::INFO;
        }
    }
    
    /**
     * Log a debug message
     */
    public static function debug(string $message, array $context = []): void
    {
        if (SnakeConfig::ENABLE_LOGGING) {
            self::getLogger()->debug($message, $context);
        }
    }
    
    /**
     * Log an info message
     */
    public static function info(string $message, array $context = []): void
    {
        if (SnakeConfig::ENABLE_LOGGING) {
            self::getLogger()->info($message, $context);
        }
    }
    
    /**
     * Log a warning message
     */
    public static function warning(string $message, array $context = []): void
    {
        if (SnakeConfig::ENABLE_LOGGING) {
            self::getLogger()->warning($message, $context);
        }
    }
    
    /**
     * Log an error message
     */
    public static function error(string $message, array $context = []): void
    {
        if (SnakeConfig::ENABLE_LOGGING) {
            self::getLogger()->error($message, $context);
        }
    }
}
