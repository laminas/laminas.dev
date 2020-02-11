<?php

declare(strict_types=1);

namespace App\GitHub\Middleware;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class GithubRequestHandlerFactory
{
    public function __invoke(ContainerInterface $container): GithubRequestHandler
    {
        return new GithubRequestHandler(
            $container->get(EventDispatcherInterface::class)
        );
    }
}
