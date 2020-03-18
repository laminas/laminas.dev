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
            $container->get(AuthorizedUserListInterface::class)
        );

        // Attach commands
        $commands->attach($container->get(BuildDocsCommand::class));
        $commands->attach($container->get(RegenerateAuthorizedUserListCommand::class));
        $commands->attach($container->get(RegisterRepoCommand::class));
        $commands->attach($container->get(TweetCommand::class));

        return $commands;
    }
}
