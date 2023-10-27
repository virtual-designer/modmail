<?php

namespace App\Commands\Mailing;

use App\Core\Command;
use App\Core\CommandContext;
use App\Facades\Mail;
use App\Log\Log;
use Discord\Parts\Channel\Message;
use Discord\Parts\Interactions\Interaction;
use ErrorException;
use Throwable;

class ReplyCommand extends Command
{
    protected string $name = "reply";
    protected bool $interactionBased = false;
    protected bool $mailOnly = true;
    protected array $aliases = ['r', 'ra', 'a', 'send', 's', 'sa'];

    /**
     * @throws ErrorException
     * @throws Throwable
     */
    public function execute(Message | Interaction $message, CommandContext $context): void
    {
        if ($context->argc < 1) {
            $this->error("Please provide a user to reply to!");
            return;
        }

        if ($context->argc < 2) {
            $this->error("Please provide a message to reply with!");
            return;
        }

        $user = $this->parseUser(array_shift($context->args));

        if (!$user) {
            $this->error("No such user found!");
            return;
        }

        $content = implode(' ', $context->args);
        $anonymous = str_ends_with($context->argv[0], 'a');

        $mail = Mail::new()
            ->withUser($user)
            ->fetch();

        if (!$mail) {
            $this->error("This user does not have an associated mail thread.");
            return;
        }

        $mail->makeReplyToUser([
            'guild_icon' => $message->guild->icon,
            'author' => $message->author,
            'content' => $content,
        ], $anonymous);

        $this->success("Reply sent to the given user.");
    }
}