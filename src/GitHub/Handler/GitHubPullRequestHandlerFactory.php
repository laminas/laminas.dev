<?php

declare(strict_types=1);

namespace App\GitHub\Handler;

use App\Slack\SlackClientInterface;
use Psr\Container\ContainerInterface;

class GitHubPullRequestHandlerFactory
{
    public function __invoke(ContainerInterface $container) : GitHubPullRequestHandler
    {
        return new GitHubPullRequestHandler(
            $container->get(SlackClientInterface::class)
        );
    }
}
