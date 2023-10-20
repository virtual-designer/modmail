<?php

namespace App\Core;

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
}