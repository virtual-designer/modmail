<?php

namespace App\Core;

use App\Log\Log;
use Discord\Parts\Channel\Message;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\User\User;
use Exception;
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
        return ($message ?? $this->message)->reply(":x: $text");
    }
    protected function success(string $text, Message | Interaction | null $message = null)
    {
        return ($message ?? $this->message)->reply("$text");
    }

    private function checkPermissions(Message | Interaction $message, CommandContext $context): bool
    {
        $systemAdmins = config()->systemAdmins;

        if (in_array($message->author->id, $systemAdmins)) {
            return true;
        }

        if ($this->systemAdminOnly) {
            return false;
        }

        if ($this->public) {
            return true;
        }

        if (in_array($message->author->id, config()->allowedUsers)) {
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
}