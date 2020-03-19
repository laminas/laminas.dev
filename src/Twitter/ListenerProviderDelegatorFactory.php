<?php

declare(strict_types=1);

namespace App\Twitter;

use Phly\EventDispatcher\ListenerProvider\AttachableListenerProvider;
use Psr\Container\ContainerInterface;

class ListenerProviderDelegatorFactory
{
    public function __invoke(ContainerInterface $container, string $name, callable $factory): AttachableListenerProvider
    {
        /** @var AttachableListenerProvider $provider */
        $provider = $factory();
        $provider->listen(Tweet::class, $container->get(TweetListener::class));
        return $provider;
    }
}
