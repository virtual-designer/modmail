<?php

namespace App\Services;

use App\Core\Service;
use App\Mail\MailBuilder;
use App\Models\Thread;
use Discord\Parts\Channel\Channel;
use Discord\Parts\User\User;
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
     * @return array [Thread, Channel]
     * @throws Throwable
     */
    public function create(array $params)
    {
        /** @var User $user */
        $user = $params['user'];

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

        await($channel->sendEmbed(
            embed()
                ->setTitle("Incoming mail")
                ->setAuthor($user->username, $user->avatar, "https://discord.com/channels/{$channel->guild_id}/{$channel->id}")
                ->setThumbnail($user->avatar)
                ->setDescription("A new thread was created by <@{$thread->createdById}>. The conversation begins here.")
                ->addFieldValues("Thread ID", $thread->id . '', true)
                ->addFieldValues("User", "{$user->username} ({$user->id})", true)
                ->addFieldValues("Created By", "{$createdBy->username} ({$createdBy->id})", true)
                ->setFooter("Thread Created")
                ->setTimestamp()
                ->setColor(0x007bff)
        ));

        return [$thread, $channel];
    }
}