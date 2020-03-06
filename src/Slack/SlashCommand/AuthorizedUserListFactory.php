<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use App\Slack\SlackClientInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;

class AuthorizedUserListFactory
{
    public function __invoke(ContainerInterface $container): AuthorizedUserList
    {
        $acl = new AuthorizedUserList(
            $container->get(SlackClientInterface::class),
            $container->get(RequestFactoryInterface::class),
            $container->get('config')['slack']['channels']['acl'] ?? AuthorizedUserList::DEFAULT_ACL_CHANNEL
        );
        $acl->build();
        return $acl;
    }
}
