<?php

namespace App\Commands\Settings;

use App\Core\Command;
use App\Core\CommandContext;
use App\Facades\Mail;
use App\Models\Thread;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Interactions\Interaction;
use Discord\Repository\Guild\ChannelRepository;
use function React\Async\await;

class TestCommand extends Command
{
    protected string $name = "test";
    protected bool $interactionBased = false;
    protected bool $public = true;

    public function execute(Message | Interaction $message, CommandContext $context): void
    {

    }
}