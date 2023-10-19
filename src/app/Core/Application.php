<?php

namespace App\Core;

use App\Events\MessageCreateEventListener;
use App\Events\ReadyEventListener;
use App\Log\Log;
use Discord\Discord;
use Discord\Exceptions\IntentException;
use Discord\WebSockets\Intents;
use Monolog\Logger;

final class Application
{
    public readonly Discord $discord;
    public readonly CommandManager $commandManager;

    public static self $instance;
    private static bool $instanceCreated = false;

    /**
     * @throws IntentException
     */
    public function __construct()
    {
        $options = $this->options();
        $this->discord = new Discord($options);

        if (!self::$instanceCreated) {
            self::$instance = $this;
            self::$instanceCreated = true;
        }
    }

    public function boot(): void
    {
        $this->commandManager = new CommandManager($this);
        $this->commandManager->autoload();
        $this->loadEventListeners();
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
            'logger' => new Logger('discord')
        ];
    }

    private function listeners(): array
    {
        return [
            ReadyEventListener::class,
            MessageCreateEventListener::class
        ];
    }

    public function start(): void
    {
        Log::debug("Starting app...");
        $this->discord->run();
    }

    private function loadEventListeners(): void
    {
        $listeners = $this->listeners();

        foreach ($listeners as $class) {
            $listener = new $class($this);
            $this->registerEventListener($listener);
        }
    }

    private function registerEventListener(EventListener $listener): void
    {
        foreach ($listener->getMethods() as $method) {
            $event = EventListener::METHOD_MAP[$method];
            $this->discord->on($event, fn (...$args) => call_user_func_array([$listener, $method], $args));
            Log::debug("Registered event {$event} (method $method)");
        }
    }
}