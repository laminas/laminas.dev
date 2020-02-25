<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use Psr\Container\ContainerInterface;

class SlashCommandsFactory
{
    public function __invoke(ContainerInterface $container): SlashCommands
    {
        $commands = new SlashCommands(
            $container->get(SlashCommandResponseFactory::class),
            $container->get(AuthorizedUserList::class)
        );

        // Attach commands
        $commands->attach($container->get(BuildDocsCommand::class));

        return $commands;
    }
}
