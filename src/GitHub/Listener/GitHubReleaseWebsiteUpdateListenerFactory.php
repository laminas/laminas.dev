<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\HttpClientInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class GitHubReleaseWebsiteUpdateListenerFactory
{
    public function __invoke(ContainerInterface $container): GitHubReleaseWebsiteUpdateListener
    {
        return new GitHubReleaseWebsiteUpdateListener(
            $container->get(HttpClientInterface::class),
            $container->get(LoggerInterface::class),
            $container->get('config')['getlaminas']['token']
        );
    }
}
