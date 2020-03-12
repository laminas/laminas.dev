<?php

declare(strict_types=1);

namespace App\Factory;

use Mezzio\Swoole\Log\SwooleLoggerFactory;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class AccessLogFactory
{
    use CreateLogHandlerTrait;

    public function __invoke(ContainerInterface $container): LoggerInterface
    {
        $config  = $container->has('config') ? $container->get('config') : [];
        $handler = $this->createLogHandler($config['mezzio-swoole']['log_handler'] ?? []);
        if (! $handler) {
            $handler = (new SwooleLoggerFactory())($container);
        }

        // Create logger
        $logger = new Logger('http', [], [new PsrLogMessageProcessor()]);
        $logger->pushHandler($handler);
        return $logger;
    }
}
