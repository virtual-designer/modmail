<?php

namespace App\Commands\Mailing;

use App\Core\Command;
use App\Core\CommandContext;
use App\Facades\Mail;
use App\Log\Log;
use Discord\Parts\Channel\Message;
use Discord\Parts\Interactions\Interaction;
use ErrorException;

class ReplyCommand extends Command
{
    protected string $name = "test";
    protected bool $interactionBased = false;
    protected bool $public = true;

    /**
     * @throws ErrorException
     */
    public function execute(Message | Interaction $message, CommandContext $context): void
    {
        [$thread, $channel] = Mail::new()
            ->withUser($message->author)
            ->inCategory($message->channel->parent_id)
            ->create();

        Log::success("Created thread: {$thread->id}");

        $channel->sendMessage("The conversation begins here!");
    }
}