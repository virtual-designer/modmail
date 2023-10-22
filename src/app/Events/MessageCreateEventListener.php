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
use ErrorException;
use Exception;
use Throwable;

class MessageCreateEventListener extends EventListener
{
    /**
     * @throws ErrorException
     * @throws Exception|Throwable
     */
    protected function directMessage(Message $message, Discord $discord): void
    {
        if ($message->content === '') {
            return;
        }

        $mail = Mail::new()
            ->withUser($message->author)
            ->withCreatedBy($message->author)
            ->withInitialMessage($message)
            ->fetchOrCreate();

        $mail->confirmUser($message);
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