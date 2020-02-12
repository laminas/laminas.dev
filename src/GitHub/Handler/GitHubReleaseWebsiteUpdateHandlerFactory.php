<?php

declare(strict_types=1);

namespace App\GitHub\Handler;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class GitHubReleaseWebsiteUpdateHandlerFactory
{
    public function __invoke(ContainerInterface $container): GitHubReleaseWebsiteUpdateHandler
    {
        return new GitHubReleaseWebsiteUpdateHandler(
            $container->get(LoggerInterface::class),
            $container->get('config')['getlaminas']['token']
        );
    }
}
