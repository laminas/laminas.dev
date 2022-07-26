<?php

declare(strict_types=1);

namespace App\Factory;

use Mezzio\Swoole\Exception\InvalidListenerException;
use Phly\EventDispatcher\ListenerProvider\AttachableListenerProvider;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;

class SwooleEventListenerDelegator
{
    public function __invoke(
        ContainerInterface $container,
        string $serviceName,
        callable $factory
    ): AttachableListenerProvider {
        $config = $container->has('config') ? $container->get('config') : [];
        Assert::isMap($config, sprintf(
            'Cannot decorate %s using %s; config service is not a map',
            AttachableListenerProvider::class,
            $this::class
        ));

        $config = $config['mezzio-swoole']['swoole-http-server']['listeners'] ?? [];
        Assert::isMap($config, sprintf(
            'Cannot decorate %s using; mezzio-swoole.swoole-http-server.listeners config is not a map',
            AttachableListenerProvider::class,
            $this::class
        ));

        $provider = $factory();
        Assert::isInstanceOf($provider, AttachableListenerProvider::class);

        foreach ($config as $event => $listeners) {
            Assert::stringNotEmpty($event);
            Assert::isList($listeners);

            foreach ($listeners as $listener) {
                Assert::true(is_string($listener) || is_callable($listener));

                /** @var AttachableListenerProvider $provider */
                $provider->listen($event, $this->prepareListener($container, $listener, $event));
            }
        }

        return $provider;
    }

    /**
     * @param string|callable $listener
     */
    private function prepareListener(ContainerInterface $container, $listener, string $event): callable
    {
        if (is_callable($listener)) {
            return $listener;
        }

        if (! $container->has($listener)) {
            throw InvalidListenerException::forNonexistentListenerType($listener, $event);
        }

        /** @psalm-suppress MixedAssignment */
        $listener = $container->get($listener);
        if (! is_callable($listener)) {
            throw InvalidListenerException::forListenerOfEvent($listener, $event);
        }

        return $listener;
    }
}
