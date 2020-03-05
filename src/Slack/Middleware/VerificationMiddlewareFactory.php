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
        if (! isset($config['slack']['verification_token'])) {
            throw new RuntimeException('Missing Slack verification token configuration');
        }

        if (! isset($config['slack']['team_id'])) {
            throw new RuntimeException('Missing Slack team id configuration');
        }

        return new VerificationMiddleware(
            $config['slack']['verification_token'],
            $config['slack']['team_id'],
            $container->get(ProblemDetailsResponseFactory::class)
        );
    }
}
