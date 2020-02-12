<?php

declare(strict_types=1);

namespace App\GitHub\Handler;

use Laminas\Twitter\Twitter;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class GitHubReleaseTweetHandlerFactory
{
    public function __invoke(ContainerInterface $container): GitHubReleaseTweetHandler
    {
        return new GitHubReleaseTweetHandler(
            $container->get(Twitter::class),
            $container->get(LoggerInterface::class)
        );
    }
}
