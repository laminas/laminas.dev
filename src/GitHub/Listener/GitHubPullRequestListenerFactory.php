<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\Slack\SlackClientInterface;
use Psr\Container\ContainerInterface;

class GitHubPullRequestListenerFactory
{
    public function __invoke(ContainerInterface $container): GitHubPullRequestListener
    {
        return new GitHubPullRequestListener(
            $container->get('config')['slack']['channels']['github'],
            $container->get(SlackClientInterface::class)
        );
    }
}
