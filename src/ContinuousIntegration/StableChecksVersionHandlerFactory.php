<?php

declare(strict_types=1);

namespace App\ContinuousIntegration;

use Mezzio\Hal\HalResponseFactory;
use Psr\Container\ContainerInterface;

class StableChecksVersionHandlerFactory
{
    public function __invoke(ContainerInterface $container): StableChecksVersionHandler
    {
        return new StableChecksVersionHandler(
            $container->get(HalResponseFactory::class)
        );
    }
}
