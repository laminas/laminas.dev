<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\Slack\SlackClientInterface;
use GuzzleHttp\Client as HttpClient;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;

class GitHubStatusListenerFactory
{
    public function __invoke(ContainerInterface $container): GitHubStatusListener
    {
        return new GitHubStatusListener(
            $container->get('config')['slack']['channels']['github'],
            $container->get(SlackClientInterface::class),
            $container->get(HttpClient::class),
            $container->get(RequestFactoryInterface::class),
            $container->get(LoggerInterface::class)
        );
    }
}
