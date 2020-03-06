<?php

declare(strict_types=1);

use Laminas\ConfigAggregator\ConfigAggregator;
use Mezzio\Swoole\StaticResourceHandler\ContentTypeFilterMiddleware;

return [
    // Toggle the configuration cache. Set this to boolean false, or remove the
    // directive, to disable configuration caching. Toggling development mode
    // will also disable it by default; clear the configuration cache using
    // `composer clear-config-cache`.
    ConfigAggregator::ENABLE_CACHE => true,

    // Enable debugging; typically used to provide debugging information within templates.
    'debug' => false,

    'discourse' => [
        'secret' => getenv('DISCOURSE_SECRET'),
    ],

    'github' => [
        'secret' => getenv('GITHUB_SECRET'),
        'token'  => getenv('GITHUB_TOKEN'),
    ],

    'mezzio' => [
        // Provide templates for the error handling middleware to use when
        // generating responses.
        'error_handler' => [
            'template_404'   => 'error::404',
            'template_error' => 'error::error',
        ],
    ],

    'slack' => [
        'acl_channel'     => getenv('SLACK_CHANNEL_ACL'),
        'channels'        => [
            'github' => getenv('SLACK_CHANNEL_GITHUB'),
        ],
        'default_channel' => 'github',
        'signing_secret'  => getenv('SLACK_SIGNING_SECRET'),
        'token'           => getenv('SLACK_TOKEN'),
    ],

    'mezzio-swoole' => [
        'enable_coroutine'   => true,
        'swoole-http-server' => [
            'process-name' => 'laminasdev',
            'host'         => '0.0.0.0',
            'port'         => (int) getenv('PORT') ?? 8888,
            'mode'         => SWOOLE_PROCESS,
            'options'      => [
                // For some reason, inside a docker container, ulimit -n, which is what
                // Swoole uses to determine this value by default, reports a ridiculously
                // high number. The value presented here is the value reported by the
                // docker host.
                'max_conn' => 1024,

                // Enable task workers.
                'task_worker_num' => 4,
            ],
            'static-files' => [
                'type-map'   => array_merge(ContentTypeFilterMiddleware::TYPE_MAP_DEFAULT, [
                    'asc' => 'application/octet-stream',
                ]),
                'gzip'       => [
                    'level' => 6,
                ],
                'directives' => [
                    // Images and fonts
                    '/\.(?:ico|png|gif|jpg|jpeg|svg|otf|eot|ttf|woff2?)$/' => [
                        'cache-control' => ['public', 'max-age=' . 60 * 60 * 24 * 365],
                        'last-modified' => true,
                        'etag'          => true,
                    ],
                    // Styles and scripts
                    '/\.(?:css|js)$/'               => [
                        'cache-control' => ['public', 'max-age=' . 60 * 60 * 24 * 30],
                        'last-modified' => true,
                        'etag'          => true,
                    ],
                ],
            ],
        ],
    ],
];
