<?php

declare(strict_types=1);

namespace App\Twitter;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;

class TweetHandlerFactory
{
    public function __invoke(ContainerInterface $container): TweetHandler
    {
        return new TweetHandler(
            $container->get(ResponseFactoryInterface::class),
            $container->get(EventDispatcherInterface::class)
        );
    }
}
