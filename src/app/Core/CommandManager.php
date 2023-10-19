<?php

namespace App\Core;

use App\Log\Log;
use Illuminate\Support\Facades\App;
use InvalidArgumentException;

class CommandManager
{
    use UsesApplication;

    protected array $commands = [];
    protected const COMMAND_DIR = __DIR__ . "/../Commands";
    protected const COMMAND_NAMESPACE = '\\App\\Commands\\';

    protected function addCommandWithName(string $name, Command $command): void
    {
        if (array_key_exists($name, $this->commands)) {
            throw new InvalidArgumentException("Command \"{$name}\" already exists in the map, cannot overwrite");
        }

        $this->commands[$name] = $command;
    }

    protected function addCommand(Command $command): void
    {
        Log::info("Loading command: {$command->getName()}");

        $this->addCommandWithName($command->getName(), $command);

        foreach ($command->getAliases() as $alias) {
            $this->addCommandWithName($alias, $command);
        }
    }

    public function autoload(): void
    {
        $files = scandir(static::COMMAND_DIR);

        foreach ($files as $fileName) {
            if (!is_file(static::COMMAND_DIR . "/" . $fileName)) {
                continue;
            }

            $command = new (static::COMMAND_NAMESPACE . explode('.', $fileName)[0])($this->application);
            $this->addCommand($command);
        }
    }

    public function getCommand(string $name): ?Command
    {
        if (!array_key_exists($name, $this->commands))
            return null;

        return $this->commands[$name];
    }

    /**
     * @return array
     */
    public function getCommands(): array
    {
        return $this->commands;
    }
}