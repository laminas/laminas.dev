<?php

declare(strict_types=1);

namespace App\GitHub\Handler;

use App\Slack\SlackClientInterface;
use Psr\Container\ContainerInterface;

class GitHubPushHandlerFactory
{
    public function __invoke(ContainerInterface $container) : GitHubPushHandler
    {
        return new GitHubPushHandler(
            $container->get(SlackClientInterface::class)
        );
    }
}
