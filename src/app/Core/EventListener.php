<?php

namespace App\Core;

use Discord\Discord;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event;

/**
 *
 * @method void onReady(Discord $discord)
 * @method void onMessageCreate(Message $message, Discord $discord)
 * @method void onInteractionCreate(Interaction $interaction, Discord $discord)
 */
abstract class EventListener
{
    protected readonly Application $application;
    protected array $methods = [];
    public const METHOD_MAP = [
        'onReady' => EventType::READY,
        'onMessageCreate' => Event::MESSAGE_CREATE,
        'onInteractionCreate' => Event::INTERACTION_CREATE,
    ];

    public function __construct(Application $application)
    {
        $this->application = $application;

        foreach (get_class_methods(static::class) as $method) {
            if (method_exists(self::class, $method) || !str_starts_with($method, 'on')) {
                continue;
            }

            $this->methods[] = $method;
        }
    }

    /**
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }
}