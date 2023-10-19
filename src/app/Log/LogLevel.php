<?php

namespace App\Log;

enum LogLevel: string
{
    case DEBUG = 'debug';
    case INFO = 'info';
    case SUCCESS = 'success';
    case ERROR = 'error';
    case WARNING = 'warn';
    case CRITICAL = 'critical';
}
