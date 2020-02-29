<?php

declare(strict_types=1);

namespace App\Slack;

use GuzzleHttp\Client as HttpClient;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class SlackClientFactory
{
    public function __invoke(ContainerInterface $container): SlackClient
    {
        $config = $container->has('config') ? $container->get('config') : [];
        if (! isset($config['slack']['token'])) {
            throw new RuntimeException('Missing a token in the Slack configuration');
        }

        return new SlackClient(
            new HttpClient(['base_uri' => 'https://slack.com/api/']),
            $config['slack']['token'],
            $container->get(RequestFactoryInterface::class),
            $container->get(LoggerInterface::class)
        );
    }
}
