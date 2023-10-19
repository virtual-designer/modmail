<?php

namespace App\Core;

use Discord\WebSockets\Event;

abstract class EventType extends Event
{
    public const READY = "ready";
}