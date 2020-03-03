<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use GuzzleHttp\Client as HttpClient;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;

class GitHubReleaseWebsiteUpdateListenerFactory
{
    public function __invoke(ContainerInterface $container): GitHubReleaseWebsiteUpdateListener
    {
        return new GitHubReleaseWebsiteUpdateListener(
            $container->get(HttpClient::class),
            $container->get(RequestFactoryInterface::class),
            $container->get(LoggerInterface::class),
            $container->get('config')['getlaminas']['token']
        );
    }
}
