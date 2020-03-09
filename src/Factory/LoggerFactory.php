<?php

declare(strict_types=1);

namespace App\Factory;

use Monolog\Handler\SlackWebhookHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use function array_key_exists;

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

            case SlackWebhookHandler::class:
                $logger->pushHandler(
                    new SlackWebhookHandler(
                        $config['webhook'],
                        null, // channel; part of webhook registration
                        null, // Bot name; part of webhook registration
                        true, // Use attachments?
                        null, // Emoji icon
                        false, // Use short attachments?
                        true, // Include context and extra data?
                        $config['level'] ?? Logger::ERROR, // Log level
                        array_key_exists('bubble', $config) ? $config['bubble'] : true,
                        $config['excludeFields'] ?? [] // Fields to exclude
                    )
                );
                break;
        }
    }
}
