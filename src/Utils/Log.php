<?php

namespace App\Utils;

class Log
{
    public static function debug(string $message): void
    {
        echo("[system:debug] {$message}\n");
    }
}