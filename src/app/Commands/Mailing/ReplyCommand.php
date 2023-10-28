<?php

namespace App\Commands\Mailing;

use App\Core\Command;
use App\Core\CommandContext;
use App\Facades\Mail;
use App\Log\Log;
use Discord\Parts\Channel\Message;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use ErrorException;
use Throwable;
use function React\Async\await;

class ReplyCommand extends Command
{
    protected string $name = "reply";
    protected bool $interactionBased = true;
    protected bool $mailOnly = true;
    protected string $description = "Sends a reply to the current thread.";
    protected array $aliases = ['r', 'ra', 'a', 'send', 's', 'sa'];

    public function getOptions(): array
    {
        return [
            $this->option()
                ->setName("user")
                ->setDescription("The target user")
                ->setType(Option::USER)
                ->setRequired(true),

            $this->option()
                ->setName("content")
                ->setDescription("The reply content")
                ->setType(Option::STRING)
                ->setRequired(true),

            $this->option()
                ->setName("anonymous")
                ->setDescription("Anonymous reply mode")
                ->setType(Option::BOOLEAN)
        ];
    }

    /**
     * @throws ErrorException
     * @throws Throwable
     */
    public function execute(Message | Interaction $message, CommandContext $context): void
    {
        $user = null;
        $content = '';
        $anonymous = false;

        if ($context->isInteraction) {
            await($message->acknowledgeWithResponse());
        }

        if ($context->isLegacy) {
            if ($context->argc < 1) {
                $this->error("Please provide a user to reply to!");
                return;
            }

            if ($context->argc < 2) {
                $this->error("Please provide a message to reply with!");
                return;
            }

            $content = implode(' ', $context->args);
            $anonymous = str_ends_with($context->argv[0], 'a');
        }
        else {
            /**
             * @var \Discord\Parts\Interactions\Request\Option $user
             */
            $user = $message->data->options->get("name", "user")?->value;
            dump($user);
            $content = $message->data->options->get("name", "content")?->value;
            $anonymous = $message->data->options->get("name", "anonymous")?->value ?? false;
        }

        $user = $this->parseUser($context->isLegacy ? array_shift($context->args) : $user);

        if (!$user) {
            $this->error("No such user found!");
            return;
        }

        $mail = Mail::new()
            ->withUser($user)
            ->fetch();

        if (!$mail) {
            $this->error("This user does not have an associated mail thread.");
            return;
        }

        $mail->makeReplyToUser([
            'guild_icon' => $message->guild->icon,
            'author' => $message->member->user,
            'content' => $content,
        ], $anonymous);

        $this->success("Reply sent to the given user.");
    }
}