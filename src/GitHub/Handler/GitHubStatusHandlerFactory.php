<?php

declare(strict_types=1);

namespace App\GitHub\Handler;

use App\Slack\SlackClientInterface;
use Psr\Container\ContainerInterface;

class GitHubStatusHandlerFactory
{
    public function __invoke(ContainerInterface $container) : GitHubStatusHandler
    {
        return new GitHubStatusHandler(
            $container->get(SlackClientInterface::class)
        );
    }
}
