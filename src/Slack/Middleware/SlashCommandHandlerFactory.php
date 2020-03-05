<?php

declare(strict_types=1);

namespace App\Slack\Middleware;

use App\Slack\SlashCommand\SlashCommands;
use Psr\Container\ContainerInterface;

class SlashCommandHandlerFactory
{
    public function __invoke(ContainerInterface $container): SlashCommandHandler
    {
        return new SlashCommandHandler(
            $container->get(SlashCommands::class)
        );
    }
}
