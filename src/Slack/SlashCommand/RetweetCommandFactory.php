<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class RetweetCommandFactory
{
    public function __invoke(ContainerInterface $container): RetweetCommand
    {
        return new RetweetCommand(
            $container->get(SlashCommandResponseFactory::class),
            $container->get(EventDispatcherInterface::class)
        );
    }
}
