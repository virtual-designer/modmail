<?php

use App\Core\Application;
use App\Services\DatabaseService;
use Discord\Builders\CommandBuilder;
use Discord\Http\Endpoint;
use Dotenv\Dotenv;

use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function React\Async\await;

require_once __DIR__ . "/vendor/autoload.php";

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$application = new Application();
$symfonyApplication = new SymfonyApplication();

function migrations_get(): array
{
    $entryFile = __DIR__ . "/.applied_migrations.json";
    return is_file($entryFile) ? json_decode(file_get_contents($entryFile)) : [];
}

function migrations_put(array $migrations): void
{
    $entryFile = __DIR__ . "/.applied_migrations.json";
    file_put_contents($entryFile, json_encode($migrations, JSON_PRETTY_PRINT));
}

/**
 * @param bool $loadOnlyApplied
 * @return array [\App\Database\Migration[], string[]]
 */
function loadMigrations(bool $loadOnlyApplied = false): array
{
    global $application;

    $appliedMigrations = migrations_get();

    $directory = __DIR__ . '/migrations';
    $migrations = scandir($directory);

    $migrations = array_filter($migrations, function ($file) use ($appliedMigrations, $loadOnlyApplied) {
        $cond = $file !== "." && $file !== ".." &&
            str_ends_with($file, ".php");
        $in_array = in_array(str_replace(search: '.php', replace: '', subject: $file), $appliedMigrations);

        if (!$loadOnlyApplied) {
            $in_array = !$in_array;
        }

        return $cond && $in_array;
    });

    $migrations = array_map(callback: function (string $migration) use ($directory, $migrations, $application) {
        $path = "$directory/$migration";
        $class = str_replace(search: '.php', replace: '', subject: $migration);
        return new $class($application);
    }, array: $migrations);

    return [$migrations, $appliedMigrations];
}

$symfonyApplication->setName("ModMail");
$symfonyApplication->setDefaultCommand("start");

$symfonyApplication
    ->register('migrate')
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($application) {
        $application->loadServices([DatabaseService::class], false);
        [$migrations, $applied] = loadMigrations();

        if (count($migrations) === 0) {
            $output->writeln("<error>No migration can be applied.</error>");
            return Command::FAILURE;
        }

        foreach ($migrations as $migration) {
            $exploded = explode('\\', get_class($migration));
            $name = end($exploded);
            $output->writeln("<info>Migrating: " . $name . "</info>");
            $migration->up();
            $applied[] = $name;
        }

        migrations_put($applied);
        return Command::SUCCESS;
    });

$symfonyApplication
    ->register('migrate:rollback')
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($application) {
        $application->loadServices([DatabaseService::class], false);
        [$migrations] = loadMigrations(true);

        if (count($migrations) === 0) {
            $output->writeln("<error>No migration was applied previously.</error>");
            return Command::FAILURE;
        }

        foreach ($migrations as $migration) {
            $exploded = explode('\\', get_class($migration));
            $name = end($exploded);
            $output->writeln("<info>Rolling back: " . $name . "</info>");
            $migration->down();
        }

        migrations_put([]);
        return Command::SUCCESS;
    });

$symfonyApplication
    ->register('migrate:refresh')
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($application, $symfonyApplication) {
        $rollbackInput = new ArrayInput([
            'command' => 'migrate:rollback',
        ]);
        $migrateInput = new ArrayInput([
            'command' => 'migrate',
        ]);

        $symfonyApplication->doRun($rollbackInput, $output);
        $symfonyApplication->doRun($migrateInput, $output);

        return Command::SUCCESS;
    });

$symfonyApplication
    ->register('start')
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($application) {
        $application->boot();
        $application->start();
        return Command::SUCCESS;
    });

$symfonyApplication
    ->register('commands:update')
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($application) {
        $application->boot();

        $application->discord->on('ready', function () use($application) {
            $guild = guildCheck();
            $commands = array_values(array_map(function (\App\Core\Command $command) {
                dump($command->getName(), $command->getDescription());

                $builder = CommandBuilder::new()
                    ->setName($command->getName())
                    ->setDescription($command->getDescription())
                    ->setType(\Discord\Parts\Interactions\Command\Command::CHAT_INPUT);

                foreach ($command->getOptions() as $option) {
                    $builder->addOption($option);
                }

                return $builder->toArray();
            }, array_filter(
                $application->commandManager->getCommands(),
                fn ($command, $key) => $command->getName() === $key && $command->isInteractionBased(), ARRAY_FILTER_USE_BOTH))
            );

            $body = json_encode($commands);

            dump($commands);

            await(discord()->getHttpClient()->put(
                Endpoint::bind(Endpoint::GUILD_APPLICATION_COMMANDS,
                    discord()->application->id,
                    $guild->id
                ),
                $body,
                [
                    'Content-Type' => 'application/json'
                ]
            ));
        });

        $application->start();
        return Command::SUCCESS;
    });

$symfonyApplication->setCatchExceptions(true);
$symfonyApplication->run();

//$application->boot();
//$application->start();