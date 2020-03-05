<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class BuildDocsCommandFactory
{
    public function __invoke(ContainerInterface $container): BuildDocsCommand
    {
        return new BuildDocsCommand(
            $container->get(SlashCommandResponseFactory::class),
            $container->get(EventDispatcherInterface::class)
        );
    }
}
