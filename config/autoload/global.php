<?php

declare(strict_types=1);

use Laminas\ConfigAggregator\ConfigAggregator;
use Mezzio\Swoole\StaticResourceHandler\ContentTypeFilterMiddleware;

$debug = (bool) getenv('DEBUG');

return [
    // Toggle the configuration cache. Set this to boolean false, or remove the
    // directive, to disable configuration caching. Toggling development mode
    // will also disable it by default; clear the configuration cache using
    // `composer clear-config-cache`.
    ConfigAggregator::ENABLE_CACHE => true,

    'base_url' => getenv('BASE_URL') ?? 'https://laminas.dev',

    // Enable debugging; typically used to provide debugging information within templates.
    'debug' => $debug,

    'discourse' => [
        'secret' => getenv('DISCOURSE_SECRET'),
    ],

    'getlaminas'   => [
        'token' => getenv('LAMINAS_API_TOKEN'),
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

    'monolog' => [
        'handlers' => [
            'default' => '%log.handler%',
        ],
    ],

    'slack' => [
        'channels'       => [
            'acl'    => getenv('SLACK_CHANNEL_ACL'),
            'github' => getenv('SLACK_CHANNEL_GITHUB'),
        ],
        'signing_secret' => getenv('SLACK_SIGNING_SECRET'),
        'token'          => getenv('SLACK_TOKEN'),
    ],

    'twitter' => [
        'access_token'        => [
            'token'  => getenv('TWITTER_ACCESS_TOKEN'),
            'secret' => getenv('TWITTER_ACCESS_SECRET'),
        ],
        'oauth_options'       => [
            'consumerKey'    => getenv('TWITTER_CONSUMER_KEY'),
            'consumerSecret' => getenv('TWITTER_CONSUMER_SECRET'),
        ],
    ],

    'mezzio-swoole' => [
        'enable_coroutine'   => true,
        'log_handler'        => '%log.handler%',
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
                    '/\.(?:css|js)$/' => [
                        'cache-control' => ['public', 'max-age=' . 60 * 60 * 24 * 30],
                        'last-modified' => true,
                        'etag'          => true,
                    ],
                ],
            ],
        ],
    ],
];
