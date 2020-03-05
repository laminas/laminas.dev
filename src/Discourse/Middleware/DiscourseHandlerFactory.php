<?php

declare(strict_types=1);

namespace App\Discourse\Middleware;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;

class DiscourseHandlerFactory
{
    public function __invoke(ContainerInterface $container): DiscourseHandler
    {
        return new DiscourseHandler(
            $container->get('config')['discourse']['url'],
            $container->get(EventDispatcherInterface::class),
            $container->get(ResponseFactoryInterface::class)
        );
    }
}
