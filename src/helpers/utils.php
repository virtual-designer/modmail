<?php

use App\Core\Application;
use App\Core\Config;
use App\Log\Log;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Guild\Guild;

function app(): Application
{
    return Application::$instance;
}

function discord(): \Discord\Discord
{
    return Application::$instance->discord;
}

function embed(): Embed
{
    return new Embed(discord());
}

function config(): Config
{
    return Application::$instance->configManager->config;
}

function guild(): ?Guild
{
    $id = config()->mainGuild;
    return $id ? discord()->guilds->get('id', $id) : null; // discord()->guilds->first()
}

function guildCheck(): Guild
{
    $guild = guild();

    if (!$guild) {
        Log::error("No guild found! Please make sure to invite the bot to a server, if it's not already. Otherwise, make sure that you've entered the right Guild ID in the configuration file!");
        discord()->close();
        exit(1);
    }

    return $guild;
}