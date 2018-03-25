<?php

declare(strict_types=1);

namespace App\GitHub\Middleware;

use Psr\Container\ContainerInterface;

class GithubRequestHandlerFactory
{
    public function __invoke(ContainerInterface $container) : GithubRequestHandler
    {
        return new GithubRequestHandler(
            $container->get('messenger.bus.command')
        );
    }
}
