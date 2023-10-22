<?php

namespace App\Events;

use App\Core\CommandContext;
use App\Core\EventListener;
use App\Facades\Mail;
use App\Log\Log;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;

class MessageCreateEventListener extends EventListener
{
    protected function directMessage(Message $message, Discord $discord): void
    {
        if ($message->content === '') {
            return;
        }

        [$thread] = Mail::new()
            ->withUser($message->author)
            ->withCreatedBy($message->author)
            ->create();

        // TODO

        $message->reply("A new mail thread has been created. We'll get back to you as soon as possible. The thread ID is **{$thread->id}**.");
    }

    public function onMessageCreate(Message $message, Discord $discord): void
    {
        if ($message->author->bot) {
            return;
        }

        if ($message->channel->type === Channel::TYPE_DM) {
            $this->directMessage($message, $discord);
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