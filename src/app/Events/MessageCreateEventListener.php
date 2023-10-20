<?php

namespace App\Events;

use App\Core\CommandContext;
use App\Core\EventListener;
use App\Log\Log;
use Discord\Discord;
use Discord\Parts\Channel\Message;

class MessageCreateEventListener extends EventListener
{
    public function onMessageCreate(Message $message, Discord $discord): void
    {
        if ($message->author->bot) {
            return;
        }

        $prefix = $this->application->configManager->config->prefix;

        if (!str_starts_with($message->content, $prefix)) {
            return;
        }

        $argv = preg_split('/\s+/m', substr($message->content, strlen($prefix)));
        $name = $argv[0];
        $command = $this->application->commandManager->getCommand($name);

        if (!$command) {
            Log::debug("Command \"{$name}\" not found");
            return;
        }

        if (!$command->isLegacy()) {
            Log::debug("Command \"{$name}\" does not support legacy mode");
            return;
        }

        $args = array_slice($argv, 1);
        $context = new CommandContext([
            'args' => $args,
            'argv' => $argv,
            'isLegacy' => true,
            'isInteraction' => false,
            'argc' => count($args),
            'prefix' => $prefix,
            'commandName' => $name
        ]);

        Log::debug("Running command: {$name}");
        $command->run($message, $context);
    }
}