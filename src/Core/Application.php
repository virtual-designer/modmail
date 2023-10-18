<?php

namespace App\Core;

use App\Utils\Log;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use Discord\WebSockets\Event;
use Discord\WebSockets\Intents;

final readonly class Application
{
    public Discord $discord;

    public function __construct()
    {
        $options = $this->options();
        $this->discord = new Discord($options);
        $this->listeners();
    }

    private function options(): array
    {
        return [
            'token' => $_ENV[EnvironmentVariable::BOT_TOKEN],
            'loadAllMembers' => true,
            'intents' => [
                Intents::GUILD_MEMBERS,
                Intents::GUILDS,
                Intents::GUILD_MESSAGES,
                Intents::MESSAGE_CONTENT,
            ],
        ];
    }

    private function listeners(): void
    {
        $this->discord->on('ready', function (Discord $discord) {
            Log::debug("Successfully logged in as {$discord->user->username}!");
        });

        $this->discord->on(Event::MESSAGE_CREATE, function (Message $message) {
            if ($message->author->bot) {
                return;
            }

            if ($message->content === "ping") {
                $embed = new Embed($this->discord);
                $embed->setColor(0x007bff);
                $embed->setDescription("Pong!");

                $message->reply(
                    MessageBuilder::new()
                        ->addEmbed($embed)
                );
            }
        });
    }

    public function start(): void
    {
        Log::debug("Starting app...");
        $this->discord->run();
    }
}