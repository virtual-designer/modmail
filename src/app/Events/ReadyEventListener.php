<?php

namespace App\Events;

use App\Core\EventListener;
use App\Log\Log;
use Discord\Discord;

class ReadyEventListener extends EventListener
{
    public function onReady(Discord $discord): void
    {
        Log::info("Logged in as {$discord->user->username}!");
    }
}