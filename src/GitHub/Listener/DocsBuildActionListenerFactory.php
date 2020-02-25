<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\GitHub\GitHubClient;
use App\Slack\SlackClient;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class DocsBuildActionListenerFactory
{
    public function __invoke(ContainerInterface $container): DocsBuildActionListener
    {
        return new DocsBuildActionListener(
            $container->get(GitHubClient::class),
            $container->get(LoggerInterface::class),
            $container->get(SlackClient::class)
        );
    }
}
