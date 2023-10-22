<?php

namespace App\Mail;

use App\Models\Thread;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\User\User;
use Exception;
use React\Promise\ExtendedPromiseInterface;
use Throwable;
use function React\Async\await;

readonly class MailThread
{
    public Thread $thread;
    public Channel $channel;
    public User $user;
    public User $createdBy;
    public bool $isNew;

    public function __construct(Thread $thread, Channel $channel, User $user, User $createdBy, bool $isNew = false)
    {
        $this->thread = $thread;
        $this->channel = $channel;
        $this->user = $user;
        $this->createdBy = $createdBy;
        $this->isNew = $isNew;
    }

    public static function from(Thread $thread, Channel $channel, User $user, User $createdBy, bool $isNew = false): static
    {
        return new static(
            thread: $thread,
            channel: $channel,
            user: $user,
            createdBy: $createdBy,
            isNew: $isNew
        );
    }

    /**
     * @throws Throwable
     */
    public function sendIntroductoryMessage(): ExtendedPromiseInterface
    {
        return $this->channel->sendEmbed(
            embed()
                ->setTitle("Incoming mail")
                ->setAuthor($this->user->username, $this->user->avatar, "https://discord.com/channels/{$this->channel->guild_id}/{$this->channel->id}")
                ->setThumbnail($this->user->avatar)
                ->setDescription("A new thread was created by <@{$this->thread->createdById}>. The conversation begins here.")
                ->addFieldValues("Thread ID", $this->thread->id . '', true)
                ->addFieldValues("User", "{$this->user->username} ({$this->user->id})", true)
                ->addFieldValues("Created By", "{$this->createdBy->username} ({$this->createdBy->id})", true)
                ->setFooter("Thread Created")
                ->setTimestamp()
                ->setColor(0x007bff)
        );
    }

    /**
     * @throws Exception
     */
    public function makeReplyToThread(?Message $forwardable): ExtendedPromiseInterface
    {
        return $this->channel->sendEmbed(
            embed()
                ->setAuthor($this->user->username, $this->user->avatar, "https://discord.com/channels/{$this->channel->guild_id}/{$this->channel->id}")
                ->setDescription($forwardable && $forwardable->content !== '' ? $forwardable->content : "*No content*")
                ->setColor(0x007bff)
                ->setFooter("Received • {$this->user->id}")
                ->setTimestamp()
        );
    }

    /**
     * @throws Exception
     */
    public function makeReplyToUser(?Message $forwardable): ExtendedPromiseInterface
    {
        return $this->channel->sendEmbed(
            embed()
                ->setAuthor($forwardable->author->username, $forwardable->author->avatar, "https://discord.com/channels/{$this->channel->guild_id}/{$this->channel->id}")
                ->setDescription($forwardable && $forwardable->content !== '' ? $forwardable->content : "*No content*")
                ->setColor(0x007bff)
                ->setFooter("Sent • {$forwardable->author->id}")
                ->setTimestamp()
        );
    }

    /**
     * Confirm the user that their message was received successfully.
     *
     * @throws Exception
     */
    public function confirmUser(Message $directMessage): ExtendedPromiseInterface
    {
        if ($this->isNew) {
            return $directMessage->reply(
                MessageBuilder::new()
                    ->addEmbed(
                        embed()
                            ->setAuthor("Thread Created", guildCheck()->icon)
                            ->setDescription("Your mail thread has been created. One of the staff will get back to you as soon as possible.\nThank you for using ModMail.")
                            ->setColor(0x007bff)
                            ->setFooter("Any further replies will also be sent to the mail thread", discord()->user->avatar)
                            ->setTimestamp()
                    )
            );
        }

        return $directMessage->react("✅");
    }
}