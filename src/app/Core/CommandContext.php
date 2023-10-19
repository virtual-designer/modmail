<?php

namespace App\Core;

class CommandContext
{
    use IsDataClass;

    public array $argv;
    public array $args;
    public bool $isLegacy;
    public bool $isInteraction;
}