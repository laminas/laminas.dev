<?php

declare(strict_types=1);

namespace App\GitHub\Handler;

use App\Asana\Service\AsanaService;
use Psr\Container\ContainerInterface;

class GitHubIssueHandlerFactory
{
    public function __invoke(ContainerInterface $container) : GitHubIssueHandler
    {
        return new GitHubIssueHandler(
            $container->get(AsanaService::class)
        );
    }
}
