<?php

declare(strict_types=1);

namespace App\Factory;

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
        switch ($config['type']) {
            case StreamHandler::class:
                $logger->pushHandler(
                    new StreamHandler(
                        $config['stream'],
                        $config['level'] ?? Logger::DEBUG,
                        array_key_exists('bubble', $config) ? $config['bubble'] : true,
                        array_key_exists('expandNewLines', $config) ? $config['expandNewLines'] : true
                    )
                );
                break;

            case SlackHandler::class:
                $logger->pushHandler(
                    new SlackHandler(
                        $config['token'],
                        sprintf('#%s', ltrim($config['channel'], '#')),
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
