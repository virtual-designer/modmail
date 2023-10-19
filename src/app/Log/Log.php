<?php

namespace App\Log;

class Log
{
    public static function print(LogLevel $level, string $message): void
    {
        switch ($level) {
            case LogLevel::INFO:
                echo("\033[1;34m");
                break;

            case LogLevel::DEBUG:
                echo("\033[2m");
                break;

            case LogLevel::SUCCESS:
                echo("\033[1;32m");
                break;

            case LogLevel::WARNING:
                echo("\033[1;33m");
                break;

            case LogLevel::ERROR:
            case LogLevel::CRITICAL:
                echo("\033[1;31m");
                break;
        }

        echo("[system:{$level->value}]\033[0m {$message}\n");
    }

    public static function debug(string $message): void
    {
        static::print(LogLevel::DEBUG, $message);
    }

    public static function info(string $message): void
    {
        static::print(LogLevel::INFO, $message);
    }

    public static function warn(string $message): void
    {
        static::print(LogLevel::WARNING, $message);
    }

    public static function success(string $message): void
    {
        static::print(LogLevel::SUCCESS, $message);
    }

    public static function error(string $message): void
    {
        static::print(LogLevel::ERROR, $message);
    }
}