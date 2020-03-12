<?php

declare(strict_types=1);

namespace App\Factory;

use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class LoggerFactory
{
    use CreateLogHandlerTrait;

    public function __invoke(ContainerInterface $container): LoggerInterface
    {
        $config   = $container->has('config') ? $container->get('config') : [];
        $handlers = $config['monolog']['handlers'] ?? [];

        // Create logger
        $logger = new Logger('app', [], [new PsrLogMessageProcessor()]);

        // Add handlers
        foreach ($handlers as $handlerConfig) {
            $this->pushHandler($logger, $handlerConfig);
        }

        return $logger;
    }

    private function pushHandler(Logger $logger, array $config): void
    {
        $handler = $this->createLogHandler($config);
        if (! $handler) {
            return;
        }
        $logger->pushHandler($handler);
    }
}
