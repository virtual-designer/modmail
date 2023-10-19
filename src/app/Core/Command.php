<?php

namespace App\Core;

use Discord\Parts\Channel\Message;
use Discord\Parts\Interactions\Interaction;

abstract class Command
{
    use UsesApplication;

    protected string $name;
    protected string $group;
    protected array $aliases = [];
    protected bool $interactionBased = true;
    protected bool $legacy = true;

    /**
     * @return bool
     */
    public function isInteractionBased(): bool
    {
        return $this->interactionBased;
    }

    /**
     * @return bool
     */
    public function isLegacy(): bool
    {
        return $this->legacy;
    }

    /**
     * @return string[]
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * @return string
     */
    public function getGroup(): string
    {
        return $this->group;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    abstract public function execute(Message | Interaction $message, CommandContext $context);

    public function run(Message | Interaction $message, CommandContext $context): void
    {
        $this->execute($message, $context);
    }
}