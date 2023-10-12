<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\GitHub\Event\GitHubRelease;
use App\Mastodon\MastodonClient;
use Psr\Log\LoggerInterface;
use Throwable;

use function in_array;
use function sprintf;
use function str_replace;

class GitHubReleaseMastodonListener
{
    private const TOOT_TEMPLATE = "Released: {package} {version}\n\n{url}";

    public function __construct(
        private MastodonClient $mastodonClient,
        private LoggerInterface $logger,
        private array $ignoreList = []
    ) {
    }

    public function __invoke(GitHubRelease $message): void
    {
        if (! $message->isPublished()) {
            return;
        }

        if (in_array($message->getPackage(), $this->ignoreList, true)) {
            return;
        }

        $toot = str_replace(
            ['{package}',             '{version}',             '{url}'],
            [$message->getPackage(),  $message->getVersion(),  $message->getUrl()],
            self::TOOT_TEMPLATE
        );

        try {
            $this->mastodonClient->statusesUpdate($toot);
        } catch (Throwable $t) {
            $this->logger->error(sprintf(
                'Error tooting release %s %s: %s',
                $message->getPackage(),
                $message->getVersion(),
                $t->getMessage()
            ));
        }
    }
}
