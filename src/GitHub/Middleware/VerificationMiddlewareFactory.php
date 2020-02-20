<?php

declare(strict_types=1);

namespace App\GitHub\Middleware;

use Psr\Container\ContainerInterface;
use RuntimeException;

class VerificationMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): VerificationMiddleware
    {
        $config = $container->has('config') ? $container->get('config') : [];
        if (! isset($config['github']['secret'])) {
            throw new RuntimeException('Missing GitHub secret configuration');
        }

        return new VerificationMiddleware($config['github']['secret']);
    }
}
