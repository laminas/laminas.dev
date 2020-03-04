<?php

declare(strict_types=1);

namespace App\GitHub;

use App\HttpClientInterface;
use Psr\Container\ContainerInterface;

class GitHubClientFactory
{
    public function __invoke(ContainerInterface $container): GitHubClient
    {
        return new GitHubClient(
            $container->get('config')['github']['token'],
            $container->get(HttpClientInterface::class)
        );
    }
}
