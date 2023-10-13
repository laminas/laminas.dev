<?php

declare(strict_types=1);

namespace App\Mastodon;

use Colorfield\Mastodon\MastodonAPI;
use Colorfield\Mastodon\MastodonOAuth;
use Psr\Container\ContainerInterface;

class MastodonClientFactory
{
    public function __invoke(ContainerInterface $container): MastodonClient
    {
        $config = $container->get('config');

        $oAuth = new MastodonOAuth(
            $config['mastodon']['name'],
            $config['mastodon']['instance']
        );
        $oAuth->config->setClientId($config['mastodon']['clientId']);
        $oAuth->config->setClientSecret($config['mastodon']['clientSecret']);
        $oAuth->config->setBearer($config['mastodon']['token']);

        return new MastodonClient(
            new MastodonAPI($oAuth->config),
        );
    }
}
