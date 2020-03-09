<?php

declare(strict_types=1);

namespace App\GitHub;

use App\HttpClientInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class GitHubClientFactory
{
    public function __invoke(ContainerInterface $container): GitHubClient
    {
        $config = $container->get('config');
        return new GitHubClient(
            $config['github']['token'],
            $container->get(HttpClientInterface::class),
            $config['debug'] ? $container->get(LoggerInterface::class) : null
        );
    }
}
