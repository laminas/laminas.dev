<?php

declare(strict_types=1);

namespace App\Factory;

use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NullHandler;
use Monolog\Handler\SlackWebhookHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

use function array_key_exists;

trait CreateLogHandlerTrait
{
    private function createLogHandler(array $config): ?HandlerInterface
    {
        switch ($config['type']) {
            case NullHandler::class:
                return new NullHandler();

            case StreamHandler::class:
                return new StreamHandler(
                    stream: $config['stream'],
                    level: $config['level'] ?? Logger::DEBUG,
                    bubble: array_key_exists('bubble', $config) ? $config['bubble'] : true,
                );

            case SlackWebhookHandler::class:
                return new SlackWebhookHandler(
                    webhookUrl: $config['webhook'],
                    channel: null, // channel; part of webhook registration
                    username: null, // Bot name; part of webhook registration
                    useAttachment: true, // Use attachments?
                    iconEmoji: null, // Emoji icon
                    useShortAttachment: false, // Use short attachments?
                    includeContextAndExtra: true, // Include context and extra data?
                    level: $config['level'] ?? Logger::ERROR, // Log level
                    bubble: array_key_exists('bubble', $config) ? $config['bubble'] : true,
                    excludeFields: $config['excludeFields'] ?? [] // Fields to exclude
                );
        }
        return null;
    }
}
