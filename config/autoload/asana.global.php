<?php

declare(strict_types=1);

use App\Asana\AsanaClientFactory;
use App\Asana\Service\AsanaService;
use App\Asana\Service\AsanaServiceFactory;
use Asana\Client as AsanaClient;

return [
    'dependencies' => [
        'factories' => [
            AsanaClient::class  => AsanaClientFactory::class,
            AsanaService::class => AsanaServiceFactory::class,
        ],
    ],

    'asana' => [
        'token'     => '',
        'workspace' => 'Personal Projects',
        'project'   => 'GitHub',
    ],
];
