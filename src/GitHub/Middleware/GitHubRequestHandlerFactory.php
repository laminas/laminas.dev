<?php

declare(strict_types=1);

namespace App\GitHub\Middleware;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class GitHubRequestHandlerFactory
{
    public function __invoke(ContainerInterface $container): GitHubRequestHandler
    {
        return new GitHubRequestHandler(
            $container->get(EventDispatcherInterface::class)
        );
    }
}
