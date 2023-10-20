<?php

namespace App\Commands;

use App\Core\Command;
use App\Core\CommandContext;
use App\Log\Log;
use Composer\InstalledVersions;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;

class EvalCommand extends Command
{
    protected string $name = "eval";
    protected bool $interactionBased = false;
    protected bool $systemAdminOnly = true;

    public function execute(Message | Interaction $message, CommandContext $context)
    {
        if ($context->argc === 0) {
            return $this->error("Please specify an expression to evaluate!");
        }

        $expression = trim(substr(trim(substr($message->content, strlen($context->prefix))), strlen($context->commandName)));
        $output = '';
        $error = false;

        ob_start();

        try {
            var_dump(eval($expression));
            $output = ob_get_clean();
        }
        catch (\Throwable $exception) {
            ob_clean();
            $output = get_class($exception) . ': ' . $exception->getMessage() . "\n\n";
            $count = 0;
            $trace = explode("\n", $exception->getTraceAsString());
            $length = count($trace);

            foreach ($trace as $item) {
                if ($count >= 5 && $count < ($length - 3)) {
                    if ($length > 6 && $count === 5) {
                        $output .= "\n[...]\n\n";
                    }

                    $count++;
                    continue;
                }

                $output .= "$item\n";
                $count++;
            }

            $error = true;
        }

        $embed = embed()
            ->setColor($error ? 0xf14a60 : config()->accentColor)
            ->setTitle($error ? "An error has occurred" : "Execution result");
        $embeds = [];

        $chunks = str_split($output, 4088);

        foreach ($chunks as $chunk) {
            if (count($embeds) > 5) {
                $remaining = count($chunks) - count($embeds);
                $embeds[] = embed()
                    ->setColor($error ? 0xf14a60 : config()->accentColor)
                    ->setDescription("```\n[+$remaining embeds]\n```");

                break;
            }

            $embeds[] = embed()
                ->setColor($error ? 0xf14a60 : config()->accentColor)
                ->setDescription("```\n$chunk\n```");
        }

        $builder = MessageBuilder::new()
            ->addEmbed($embed, ...$embeds);

        return $message->reply($builder);
    }
}