<?php

declare(strict_types=1);

namespace App\Slack;

use Phly\EventDispatcher\ListenerProvider\AttachableListenerProvider;
use Psr\Container\ContainerInterface;

class ListenerProviderDelegatorFactory
{
    public function __invoke(ContainerInterface $container, string $name, callable $factory): AttachableListenerProvider
    {
        $provider = $factory();
        $provider->listen(
            Event\RegenerateAuthorizedUserList::class,
            $container->get(Listener\RegenerateAuthorizedUserListListener::class)
        );
        return $provider;
    }
}
