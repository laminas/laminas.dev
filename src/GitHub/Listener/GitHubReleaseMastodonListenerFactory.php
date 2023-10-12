<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\Mastodon\MastodonClient;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class GitHubReleaseMastodonListenerFactory
{
    public function __invoke(ContainerInterface $container): GitHubReleaseMastodonListener
    {
        $config = $container->get('config');

        return new GitHubReleaseMastodonListener(
            $container->get(MastodonClient::class),
            $container->get(LoggerInterface::class),
            $config['mastodon']['ignore_list'] ?? [],
        );
    }
}
