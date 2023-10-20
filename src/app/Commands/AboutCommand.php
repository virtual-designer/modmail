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

class AboutCommand extends Command
{
    protected string $name = "about";
    protected bool $interactionBased = false;
    protected bool $public = true;

    public function execute(Message | Interaction $message, CommandContext $context): void
    {
        $license = "[GNU Affero General Public License v3](https://www.gnu.org/licenses/agpl-3.0.en.html)";
        $package = InstalledVersions::getRootPackage();
        $version = $package['version'];
        $github = "[GitHub](https://github.com/virtual-designer/modmail)";
        $author = "[Ar Rakin](https://virtual-designer.github.io/)";
        $support = "rakinar2@onesoftnet.eu.org";

        $embed = embed()
            ->setColor(config()->accentColor)
            ->setAuthor("ModMail", discord()->user->avatar)
            ->setDescription("__**A free and open-source ModMail bot written in PHP.**__\n
                This bot is free software, and you are welcome to redistribute it under certain conditions.
                See the $license for more detailed information.
            ")
            ->addFieldValues("Version", $version, true)
            ->addFieldValues("Source Code", $github, true)
            ->addFieldValues("License", $license, true)
            ->addFieldValues("Author", $author, true)
            ->addFieldValues("Support", $support, true)
            ->setFooter("Copyright © Ar Rakin " . date('Y') . ". All rights reserved.");

        $builder = MessageBuilder::new()
            ->addEmbed($embed);

        $message->reply($builder);
    }
}