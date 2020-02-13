<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\Slack\SlackClientInterface;
use Psr\Container\ContainerInterface;

class GitHubPushListenerFactory
{
    public function __invoke(ContainerInterface $container) : GitHubPushListener
    {
        return new GitHubPushListener(
            $container->get(SlackClientInterface::class)
        );
    }
}
