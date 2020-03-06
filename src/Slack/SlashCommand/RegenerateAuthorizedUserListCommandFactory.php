<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class RegenerateAuthorizedUserListCommandFactory
{
    public function __invoke(ContainerInterface $container): RegenerateAuthorizedUserListCommand
    {
        return new RegenerateAuthorizedUserListCommand(
            $container->get(SlashCommandResponseFactory::class),
            $container->get(EventDispatcherInterface::class),
            $container->get('config')['slack']['channels']['acl'] ?? AuthorizedUserList::DEFAULT_ACL_CHANNEL
        );
    }
}
