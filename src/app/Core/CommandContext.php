<?php

namespace App\Core;

use Discord\Parts\Interactions\Interaction;

class CommandContext
{
    use IsDataClass;

    public array $argv;
    public array $args;
    public int $argc;
    public bool $isLegacy;
    public bool $isInteraction;
    public string $prefix;
    public string $commandName;
    public Interaction $interaction;
}