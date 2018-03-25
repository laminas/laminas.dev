<?php

declare(strict_types=1);

use App\Container\LoggerFactory;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

return [
    'dependencies' => [
        'factories' => [
            LoggerInterface::class => LoggerFactory::class,
        ],
    ],

    'monolog' => [
        'handlers' => [
            [
                'type'   => StreamHandler::class,
                'stream' => 'data/log/app-{date}.log',
                'level'  => Logger::DEBUG,
            ],
        ],
    ],
];
