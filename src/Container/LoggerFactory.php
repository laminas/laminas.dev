<?php

declare(strict_types=1);

namespace App\Container;

use Monolog\Handler\SlackHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use function gmdate;
use function str_replace;

class LoggerFactory
{
    public function __invoke(ContainerInterface $container) : LoggerInterface
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

    private function pushHandler(Logger $logger, array $config) : void
    {
        switch ($config['type']) {
            case StreamHandler::class:
                $logger->pushHandler(
                    new StreamHandler(
                        str_replace('{date}', gmdate('Y-m-d'), $config['stream']),
                        $config['level']
                    )
                );
                break;

            case SlackHandler::class:
                $logger->pushHandler(
                    new SlackHandler(
                        $config['token'],
                        $config['channel'],
                        $config['name'],
                        true,
                        null,
                        $config['level']
                    )
                );
                break;
        }
    }
}
