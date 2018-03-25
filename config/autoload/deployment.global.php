<?php

declare(strict_types=1);

return [
    'deployment' => [
        'private_key'  => 'config/deploy_rsa',
        'github_token' => '',

        'projects' => [
            'xtreamlabs' => [
                'repository'   => 'xtreamwayz/xtreamlabs.com',
                'environments' => [
                    'production' => [
                        'host' => '37.97.132.157',
                        'path' => '/var/www/xtreamlabs.com',
                    ],
                ],
            ],

            'xtreamwayz' => [
                'repository'   => 'xtreamwayz/xtreamwayz.com',
                'environments' => [
                    'production' => [
                        'host' => '37.97.132.157',
                        'path' => '/var/www/xtreamwayz.com',
                    ],
                ],
            ],

            'omfs' => [
                'repository'   => 'xtreamwayz/omfs.eu',
                'environments' => [
                    'production' => [
                        'host' => '37.97.132.157',
                        'path' => '/var/www/omfs.eu',
                    ],
                ],
            ],

            'aitotal-nl' => [
                'repository'   => 'xtreamwayz/aitotal-nl',
                'environments' => [
                    'production' => [
                        'host' => '136.144.133.16',
                        'path' => '/var/www/aitotal-nl',
                    ],
                ],
            ],

            'aitotal-com' => [
                'repository'   => 'xtreamwayz/aitotal-nl',
                'environments' => [
                    'production' => [
                        'host' => '136.144.133.16',
                        'path' => '/var/www/aitotal-int',
                    ],
                ],
            ],

            'auctio' => [
                'repository'   => 'xtreamwayz/auctio',
                'environments' => [
                    'production' => [
                        'host' => '149.210.215.15',
                        'path' => '/var/www/auction.production',
                    ],
                    'staging'    => [
                        'host' => '149.210.215.15',
                        'path' => '/var/www/auction.staging',
                    ],
                ],
            ],

            'gc-portal' => [
                'repository'   => 'xtreamwayz/gc-portal',
                'environments' => [
                    'production' => [
                        'host' => '136.144.133.16',
                        'path' => '/var/www/my.diamondgenetics.nl',
                    ],
                ],
            ],
        ],
    ],
];
