<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\Slack\SlackClient;
use Psr\Container\ContainerInterface;

class GitHubReleaseSlackListenerFactory
{
    public function __invoke(ContainerInterface $container): GitHubReleaseSlackListener
    {
        return new GitHubReleaseSlackListener(
            $container->get('config')['slack']['channels']['github'],
            $container->get(SlackClient::class)
        );
    }
}
