<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\GitHub\GitHubClient;
use App\Slack\SlackClientInterface;
use App\UrlHelper;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class RegisterWebhookListenerFactory
{
    public function __invoke(ContainerInterface $container): RegisterWebhookListener
    {
        return new RegisterWebhookListener(
            $container->get('config')['github']['secret'],
            $container->get(GitHubClient::class),
            $container->get(UrlHelper::class),
            $container->get(LoggerInterface::class),
            $container->get(SlackClientInterface::class)
        );
    }
}
