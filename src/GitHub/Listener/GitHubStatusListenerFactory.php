<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\GitHub\GitHubClient;
use App\Slack\SlackClientInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class GitHubStatusListenerFactory
{
    public function __invoke(ContainerInterface $container): GitHubStatusListener
    {
        return new GitHubStatusListener(
            $container->get('config')['slack']['channels']['github'],
            $container->get(SlackClientInterface::class),
            $container->get(GitHubClient::class),
            $container->get(LoggerInterface::class)
        );
    }
}
