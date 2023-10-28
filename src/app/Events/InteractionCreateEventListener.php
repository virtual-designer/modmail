<?php

namespace App\Events;

use App\Core\CommandContext;
use App\Core\EventListener;
use App\Log\Log;
use Discord\Discord;
use Discord\InteractionType;
use Discord\Parts\Interactions\Interaction;

class InteractionCreateEventListener extends EventListener
{
    public function onInteractionCreate(Interaction $interaction, Discord $discord): void
    {
        if ($interaction->type !== InteractionType::APPLICATION_COMMAND) {
            return;
        }

        $name = $interaction->data->name;
        $command = $this->application->commandManager->getCommand($name);

        if (!$command) {
            Log::debug("Command \"{$name}\" not found");
            return;
        }

        if (!$command->isInteractionBased()) {
            Log::debug("Command \"{$name}\" does not support application command mode");
            return;
        }

        $context = new CommandContext([
            'isLegacy' => false,
            'isInteraction' => true,
            'commandName' => $name,
            'interaction' => $interaction
        ]);

        Log::debug("Running application command: {$name}");
        $command->run($interaction, $context);
    }
}