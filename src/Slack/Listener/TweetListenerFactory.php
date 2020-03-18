<?php

declare(strict_types=1);

namespace App\Slack\Listener;

use App\HttpClientInterface;
use App\Slack\SlackClientInterface;
use Psr\Container\ContainerInterface;

class TweetListenerFactory
{
    public function __invoke(ContainerInterface $container): TweetListener
    {
        return new TweetListener(
            $container->get(Twitter::class),
            $container->get(SlackClientInterface::class),
            $container->get(HttpClientInterface::class)
        );
    }
}
