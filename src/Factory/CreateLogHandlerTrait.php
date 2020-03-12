<?php

declare(strict_types=1);

namespace App\Factory;

use Monolog\Handler\HandlerInterface;
use Monolog\Handler\SlackWebhookHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

use function array_key_exists;

trait CreateLogHandlerTrait
{
    private function createLogHandler(array $config): ?HandlerInterface
    {
        switch ($config['type']) {
            case StreamHandler::class:
                return new StreamHandler(
                    $config['stream'],
                    $config['level'] ?? Logger::DEBUG,
                    array_key_exists('bubble', $config) ? $config['bubble'] : true,
                    array_key_exists('expandNewLines', $config) ? $config['expandNewLines'] : true
                );

            case SlackWebhookHandler::class:
                return new SlackWebhookHandler(
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
                );
        }
        return null;
    }
}
