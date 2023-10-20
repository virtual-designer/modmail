<?php

use App\Core\Application;
use App\Core\Config;
use Discord\Parts\Embed\Embed;

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