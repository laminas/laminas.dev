<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use Psr\Container\ContainerInterface;

class SlashCommandsFactory
{
    public function __invoke(ContainerInterface $container): SlashCommands
    {
        return new SlashCommands(
            $container->get(SlashCommandResponseFactory::class)
        );
    }
}
