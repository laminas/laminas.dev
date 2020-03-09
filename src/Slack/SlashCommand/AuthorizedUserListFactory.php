<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use App\Slack\SlackClientInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;

class AuthorizedUserListFactory
{
    public function __invoke(ContainerInterface $container): AuthorizedUserList
    {
        $config = $container->get('config');
        $acl    = new AuthorizedUserList(
            $container->get(SlackClientInterface::class),
            $container->get(RequestFactoryInterface::class),
            $config['slack']['channels']['acl'] ?? AuthorizedUserList::DEFAULT_ACL_CHANNEL,
            $config['debug'] ? $container->get(LoggerInterface::class) : null
        );
        $acl->build();
        return $acl;
    }
}
