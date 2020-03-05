<?php

declare(strict_types=1);

namespace App\Discourse\Listener;

use App\Slack\SlackClientInterface;
use Psr\Container\ContainerInterface;

class DiscoursePostListenerFactory
{
    public function __invoke(ContainerInterface $container): DiscoursePostListener
    {
        return new DiscoursePostListener(
            $container->get(SlackClientInterface::class)
        );
    }
}
