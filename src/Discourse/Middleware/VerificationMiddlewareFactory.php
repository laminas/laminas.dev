<?php

declare(strict_types=1);

namespace App\Discourse\Middleware;

use App\Slack\Middleware\VerificationMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

class VerificationMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): VerificationMiddleware
    {
        return new VerificationMiddleware(
            $container->get('config')['discourse']['secret'],
            $container->get(ResponseFactoryInterface::class)
        );
    }
}
