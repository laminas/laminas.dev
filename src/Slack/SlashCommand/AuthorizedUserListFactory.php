<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use App\Slack\SlackClient;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;

class AuthorizedUserListFactory
{
    public function __invoke(ContainerInterface $container): AuthorizedUserList
    {
        return new AuthorizedUserList(
            $container->get(SlackClient::class),
            $container->get(RequestFactoryInterface::class)
        );
    }
}
