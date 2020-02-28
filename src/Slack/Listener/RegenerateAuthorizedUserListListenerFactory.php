<?php

declare(strict_types=1);

namespace App\Slack\Listener;

use App\Slack\SlackClientInterface;
use App\Slack\SlashCommand\AuthorizedUserListInterface;
use Psr\Container\ContainerInterface;

class RegenerateAuthorizedUserListListenerFactory
{
    public function __invoke(ContainerInterface $container): RegenerateAuthorizedUserListListener
    {
        return new RegenerateAuthorizedUserListListener(
            $container->get(AuthorizedUserListInterface::class),
            $container->get(SlackClientInterface::class)
        );
    }
}
