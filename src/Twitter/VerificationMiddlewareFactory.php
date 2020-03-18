<?php

declare(strict_types=1);

namespace App\Twitter;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

class VerificationMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): VerificationMiddleware
    {
        return new VerificationMiddleware(
            $container->get('config')['twitter']['tweet_verification_token'],
            $container->get(ResponseFactoryInterface::class)
        );
    }
}
