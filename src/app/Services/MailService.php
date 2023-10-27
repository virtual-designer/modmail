<?php

namespace App\Services;

use App\Core\Service;
use App\Mail\MailBuilder;
use App\Mail\MailThread;
use App\Models\Thread;
use Discord\Parts\Channel\Attachment;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\User\User;
use React\Promise\ExtendedPromiseInterface;
use Throwable;
use function React\Async\await;

class MailService extends Service
{
    /**
     * @var Thread[]
     */
    protected array $cachedThreads = [];

    public function new(): MailBuilder
    {
        return new MailBuilder($this->application);
    }

    /**
     * @param array $params
     * @return MailThread
     * @throws Throwable
     */
    public function create(array $params): MailThread
    {
        /** @var User $user */
        $user = $params['user'];

        /** @var Message|null $message */
        $message = $params['message'];

        $partialChannel = guildCheck()->channels->create(attributes: [
            'name' => $user->username,
            'parent_id' => $params['categoryId'],
            'type' => Channel::TYPE_TEXT
        ]);

        /** @var Channel $channel */
        $channel = await(guildCheck()->channels->save($partialChannel, "Creating new mail thread for {$user->username}"));
        $createdBy = $params['createdBy'] ?? discord()->user;

        /** @var Thread $thread */
        $thread = Thread::create([
            'userId' => $user->id,
            'createdById' => $createdBy->id,
            'channelId' => $channel->id
        ]);

        $mailThread = new MailThread(
            thread: $thread,
            channel: $channel,
            user: $user,
            createdBy: $createdBy,
            isNew: true
        );

        await($mailThread->sendIntroductoryMessage());

        if ($message) {
            await($mailThread->makeReplyToThread($message));
        }

        return $mailThread;
    }

    public function fetch(array $params): ?MailThread
    {
        /** @var User $user */
        $user = $params['user'];

        $thread = Thread::where('userId', $user->id)
            ->where('isArchived', false)
            ->first();

        if (!$thread) {
            return null;
        }

        /** @var Channel $channel */
        $channel = discord()->getChannel($thread->channelId);

        /** @var Message|null $message */
        $message = $params['message'];

        $mailThread = new MailThread(
            thread: $thread,
            channel: $channel,
            user: $user,
        );

        if ($message) {
            await($mailThread->makeReplyToThread($message));
        }

        return $mailThread;
    }

    /**
     * @throws Throwable
     */
    public function fetchOrCreate(array $params): MailThread
    {
        $mailThread = $this->fetch($params);

        if (!$mailThread) {
            return $this->create($params);
        }

        return $mailThread;
    }

    /**
     * @throws Throwable
     */
    protected function sendMessageInThreadChannel(Channel $channel, User $user, ?string $content = null): ExtendedPromiseInterface
    {
        return $channel->sendEmbed(
            embed()
                ->setAuthor($user->username, $user->avatar, "https://discord.com/channels/{$channel->guild_id}/{$channel->id}")
                ->setDescription($content ?? "*No content*")
                ->setColor(0x007bff)
                ->setFooter("Received ● {$user->id}")
                ->setTimestamp()
        );
    }
}