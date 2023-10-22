<?php

namespace App\Core;

use App\Events\MessageCreateEventListener;
use App\Events\ReadyEventListener;
use App\Log\Log;
use App\Services\ConfigManager;
use App\Services\DatabaseService;
use App\Services\MailService;
use Discord\Discord;
use Discord\Exceptions\IntentException;
use Discord\WebSockets\Intents;
use Monolog\Logger;
use Symfony\Component\VarDumper\Cloner\Data;

final class Application
{

    public static self $instance;
    private static bool $instanceCreated = false;
    public Discord $discord;
    public readonly CommandManager $commandManager;
    public readonly ConfigManager $configManager;
    public readonly DatabaseService $databaseService;
    public readonly MailService $mailService;

    public function __construct(public readonly ?string $configFilePath = null)
    {
        if (!self::$instanceCreated) {
            self::$instance = $this;
            self::$instanceCreated = true;
        }
    }

    /**
     * @throws IntentException
     */
    public function boot(?array $services = null): void
    {
        $options = $this->options();
        $this->discord = new Discord($options);
        $this->loadServices($services);
        $this->commandManager = new CommandManager($this);
        $this->commandManager->autoload();
        $this->loadEventListeners();
    }

    private function options(): array
    {
        return [
            'token' => $_ENV[Env::BOT_TOKEN],
            'loadAllMembers' => true,
            'intents' => [
                Intents::GUILD_MEMBERS,
                Intents::GUILDS,
                Intents::GUILD_MESSAGES,
                Intents::MESSAGE_CONTENT,
                Intents::DIRECT_MESSAGES,
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

    private function services(): array
    {
        return [
            'configManager' => ConfigManager::class,
            'databaseService' => DatabaseService::class,
            'mailService' => MailService::class,
        ];
    }

    public function loadServices(?array $services = null, bool $log = true): void
    {
        foreach ($services ?? $this->services() as $key => $service) {
            if ($log) {
                Log::info("Loading service: {$service}");
            }

            $this->$key = new $service($this);
            $this->$key->boot();
        }
    }
}