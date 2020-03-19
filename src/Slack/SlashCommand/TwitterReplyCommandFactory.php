<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class TwitterReplyCommandFactory
{
    public function __invoke(ContainerInterface $container): TwitterReplyCommand
    {
        return new TwitterReplyCommand(
            $container->get(SlashCommandResponseFactory::class),
            $container->get(EventDispatcherInterface::class)
        );
    }
}
