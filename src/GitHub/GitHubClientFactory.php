<?php

declare(strict_types=1);

namespace App\GitHub;

use GuzzleHttp\Client as HttpClient;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;

class GitHubClientFactory
{
    public function __invoke(ContainerInterface $container): GitHubClient
    {
        return new GitHubClient(
            $container->get('config')['github']['token'],
            $container->get(RequestFactoryInterface::class),
            $container->get(HttpClient::class)
        );
    }
}
