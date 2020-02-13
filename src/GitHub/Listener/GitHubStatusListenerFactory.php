<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\Slack\SlackClientInterface;
use Psr\Container\ContainerInterface;

class GitHubStatusListenerFactory
{
    public function __invoke(ContainerInterface $container) : GitHubStatusListener
    {
        return new GitHubStatusListener(
            $container->get(SlackClientInterface::class)
        );
    }
}
