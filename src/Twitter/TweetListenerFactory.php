<?php

declare(strict_types=1);

namespace App\Twitter;

use App\Slack\SlackClientInterface;
use Psr\Container\ContainerInterface;

class TweetListenerFactory
{
    public function __invoke(ContainerInterface $container): TweetListener
    {
        return new TweetListener(
            $container->get(SlackClientInterface::class),
            $container->get('config')['twitter']['slack_channel'] ?? TweetListener::DEFAULT_SLACK_CHANNEL
        );
    }
}
