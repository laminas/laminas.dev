<?php

declare(strict_types=1);

namespace App\Slack\Middleware;

use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use Psr\Container\ContainerInterface;
use RuntimeException;

class VerificationMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): VerificationMiddleware
    {
        $config = $container->has('config') ? $container->get('config') : [];
        if (! isset($config['slack']['signing_secret'])) {
            throw new RuntimeException('Missing Slack signing_secret configuration');
        }

        return new VerificationMiddleware(
            $config['slack']['signing_secret'],
            $container->get(ProblemDetailsResponseFactory::class)
        );
    }
}
