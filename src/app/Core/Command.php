<?php

namespace App\Core;

use App\Log\Log;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Channel\Message;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\User\User;
use Throwable;
use function React\Async\await;

abstract class Command
{
    use UsesApplication;

    protected string $name;
    protected string $group;
    protected array $aliases = [];
    protected bool $interactionBased = true;
    protected bool $legacy = true;
    protected bool $systemAdminOnly = false;
    protected bool $public = false;
    protected bool $mailOnly = false;
    protected string $description = '';
    private Message | Interaction | null $message = null;
    private CommandContext | null $context = null;

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

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return Option[]
     */
    public function getOptions(): array
    {
        return [];
    }

    protected function option(): Option
    {
        return new Option($this->application->discord);
    }

    abstract public function execute(Message | Interaction $message, CommandContext $context);

    public function run(Message | Interaction $message, CommandContext $context): void
    {
        $this->message = $message;
        $this->context = $context;

        if ($this->validate($message, $context)) {
            $this->execute($message, $context);
        }

        $this->message = null;
        $this->context = null;
    }

    protected function error(string $text, Message | Interaction | null $message = null)
    {
        return $this->reply(builder: MessageBuilder::new()->setContent(":x: $text"), message: $message);
    }
    protected function success(string $text, Message | Interaction | null $message = null)
    {
        return $this->reply(builder: MessageBuilder::new()->setContent("$text"), message: $message);
    }

    private function checkPermissions(Message | Interaction $message, CommandContext $context): bool
    {
        $systemAdmins = config()->systemAdmins;

        if (in_array($message->member->id, $systemAdmins)) {
            return true;
        }

        if ($this->systemAdminOnly) {
            return false;
        }

        if ($this->public) {
            return true;
        }

        if (in_array($message->member->id, config()->allowedUsers)) {
            return true;
        }

        foreach (config()->allowedRoles as $allowedRole) {
            if ($message->member->roles->has($allowedRole)) {
                return true;
            }
        }

        return false;
    }

    private function validate(Message | Interaction $message, CommandContext $context): bool
    {
        if (!$this->checkPermissions($message, $context)) {
            $this->error("You don't have permission to run this command.");
            return false;
        }

        if ($this->mailOnly && $message->channel->parent_id !== config()->mailCategory) {
            $this->error("This command must be run inside a mail thread!");
            return false;
        }

        return true;
    }

    public function parseUser(int | string $arg): ?User
    {
        $arg = is_int($arg) ? $this->context->args[$arg] : $arg;
        $id = preg_match('/^<@(!)?\d+>$/', $arg) ? substr($arg, str_contains($arg, '!') ? 3 : 2, -1) : $arg;

        try {
            return await($this->application->discord->users->fetch($id));
        }
        catch (Throwable $exception) {
            Log::error($exception->__toString());
        }

        return null;
    }

    public function reply(MessageBuilder | string $builder, bool $ephemeral = false, ?Message $message = null)
    {
        if (is_string($builder)) {
            $builder = MessageBuilder::new()->setContent($builder);
        }

        $message = ($message ?? $this->message);

        if ($this->context->isInteraction) {
            if ($message->isResponded()) {
                return $message->updateOriginalResponse($builder);
            }

            return $message->respondWithMessage($builder, $ephemeral);
        }

        return $message->reply($builder);
    }
}