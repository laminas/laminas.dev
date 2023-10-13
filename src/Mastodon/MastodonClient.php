<?php

declare(strict_types=1);

namespace App\Mastodon;

use Colorfield\Mastodon\MastodonAPI;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

use function mb_strlen;

class MastodonClient
{
    public function __construct(
        private MastodonAPI $mastodon,
    ) {
    }

    /**
     * @return mixed
     * @throws GuzzleException|RuntimeException|Exception
     */
    public function statusesUpdate(string $status)
    {
        $path = 'statuses';
        if (0 === mb_strlen($status)) {
            throw new RuntimeException(
                'Status must contain at least one character'
            );
        }

        return $this->mastodon->post('/statuses', [
            'status'     => $status,
            'visibility' => 'public',
        ]);
    }
}
