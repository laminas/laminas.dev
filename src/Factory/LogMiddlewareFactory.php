<?php

declare(strict_types=1);

namespace App\Factory;

use App\LogMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class LogMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): LogMiddleware
    {
        return new LogMiddleware(
            $container->get(LoggerInterface::class)
        );
    }
}
