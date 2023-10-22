<?php

namespace App\Facades;

use App\Core\Facade;
use App\Mail\MailBuilder;
use App\Services\MailService;

/**
 * @method static MailBuilder new()
 */
abstract class Mail extends Facade
{
    protected static function target(): MailService
    {
        return app()->mailService;
    }
}