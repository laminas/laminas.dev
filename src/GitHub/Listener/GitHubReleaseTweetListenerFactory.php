<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use Laminas\Twitter\Twitter;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class GitHubReleaseTweetListenerFactory
{
    public function __invoke(ContainerInterface $container): GitHubReleaseTweetListener
    {
        return new GitHubReleaseTweetListener(
            $container->get(Twitter::class),
            $container->get(LoggerInterface::class)
        );
    }
}
