<?php

declare(strict_types=1);

namespace App\Slack\Listener;

use App\Slack\SlackClientInterface;
use App\Slack\SlashCommand\AuthorizedUserList;
use Psr\Container\ContainerInterface;

class RegenerateAuthorizedUserListListenerFactory
{
    public function __invoke(ContainerInterface $container): RegenerateAuthorizedUserListListener
    {
        return new RegenerateAuthorizedUserListListener(
            $container->get(AuthorizedUserList::class),
            $container->get(SlackClientInterface::class)
        );
    }
}
