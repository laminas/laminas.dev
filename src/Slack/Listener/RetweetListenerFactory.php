<?php

declare(strict_types=1);

namespace App\Slack\Listener;

use App\Slack\SlackClientInterface;
use Psr\Container\ContainerInterface;

class RetweetListenerFactory
{
    public function __invoke(ContainerInterface $container): RetweetListener
    {
        return new RetweetListener(
            $container->get(Twitter::class),
            $container->get(SlackClientInterface::class)
        );
    }
}
