<?php

declare(strict_types=1);

namespace App\Discourse\Middleware;

use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use Psr\Container\ContainerInterface;

class VerificationMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): VerificationMiddleware
    {
        return new VerificationMiddleware(
            $container->get('config')['discourse']['secret'],
            $container->get(ProblemDetailsResponseFactory::class)
        );
    }
}
