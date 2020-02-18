<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class SlashCommandResponseFactoryFactory
{
    public function __invoke(ContainerInterface $container):  SlashCommandResponseFactory
    {
        return new SlashCommandResponseFactory(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class)
        );
    }
}
