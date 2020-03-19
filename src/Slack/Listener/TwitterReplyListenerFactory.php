<?php

declare(strict_types=1);

namespace App\Slack\Listener;

use App\Slack\SlackClientInterface;
use Laminas\Twitter\Twitter;
use Psr\Container\ContainerInterface;

class TwitterReplyListenerFactory
{
    public function __invoke(ContainerInterface $container): TwitterReplyListener
    {
        return new TwitterReplyListener(
            $container->get(Twitter::class),
            $container->get(SlackClientInterface::class)
        );
    }
}
