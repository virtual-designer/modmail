<?php

namespace App\Commands\Settings;

use App\Core\Command;
use App\Core\CommandContext;
use App\Models\Thread;
use Discord\Parts\Channel\Message;
use Discord\Parts\Interactions\Interaction;

class TestCommand extends Command
{
    protected string $name = "test";
    protected bool $interactionBased = false;
    protected bool $public = true;

    public function execute(Message | Interaction $message, CommandContext $context): void
    {
        $thread = Thread::create([
            "channelId" => $message->channel_id,
            "userId" => $message->member->user->id,
            "createdById" => discord()->user->id
        ]);

        dump($thread);

        $this->success("Successfully inserted entry with ID: {$thread->id}");
    }
}