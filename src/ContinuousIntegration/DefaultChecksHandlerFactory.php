<?php

declare(strict_types=1);

namespace App\ContinuousIntegration;

use Mezzio\Hal\HalResponseFactory;
use Psr\Container\ContainerInterface;

class DefaultChecksHandlerFactory
{
    public function __invoke(ContainerInterface $container): DefaultChecksHandler
    {
        return new DefaultChecksHandler(
            $container->get(HalResponseFactory::class)
        );
    }
}
