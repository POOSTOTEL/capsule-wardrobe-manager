<?php
// app/Utils/Logger.php

namespace App\Utils;

class Logger
{
    private static $logPath = null;

    public static function init(): void
    {
        if (self::$logPath === null) {
            self::$logPath = defined('LOG_PATH') ? LOG_PATH : dirname(__DIR__, 2) . '/logs';
            
            // Создаем директорию логов, если её нет
            if (!is_dir(self::$logPath)) {
                mkdir(self::$logPath, 0755, true);
            }
        }
    }

    public static function log(string $message, string $level = 'INFO', array $context = []): void
    {
        self::init();
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logMessage = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;
        
        $logFile = self::$logPath . '/app.log';
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log($message, 'ERROR', $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log($message, 'WARNING', $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log($message, 'INFO', $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        self::log($message, 'DEBUG', $context);
    }
}
