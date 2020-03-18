<?php

declare(strict_types=1);

namespace App\Twitter;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;

class TweetHandlerFactory
{
    public function __invoke(ContainerInterface $container): TweetHandler
    {
        return new TweetHandler(
            $container->get(ResponseFactoryInterface::class),
            $container->get(LoggerInterface::class)
        );
    }
}
