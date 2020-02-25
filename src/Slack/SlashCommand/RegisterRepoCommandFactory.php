<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class RegisterRepoCommandFactory
{
    public function __invoke(ContainerInterface $container): RegisterRepoCommand
    {
        return new RegisterRepoCommand(
            $container->get(SlashCommandResponseFactory::class),
            $container->get(EventDispatcherInterface::class)
        );
    }
}
