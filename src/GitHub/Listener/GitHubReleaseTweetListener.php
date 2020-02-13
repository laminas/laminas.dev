<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\GitHub\Event\GitHubRelease;
use Laminas\Twitter\Twitter;
use Psr\Log\LoggerInterface;

class GitHubReleaseTweetListener
{
    private const TWEET_TEMPLATE = "Released: {package} {version}\n\n{url}";

    /** @var LoggerInterface */
    private $logger;

    /** @var Twitter */
    private $twitter;

    public function __construct(
        Twitter $twitterClient,
        LoggerInterface $logger
    ) {
        $this->twitter = $twitterClient;
        $this->logger  = $logger;
    }

    public function __invoke(GitHubRelease $message): void
    {
        if (! $message->isPublished()) {
            return;
        }

        if (! $this->prepareTwitterClient()) {
            $this->logger->error(sprintf(
                'Could not validate twitter credentials; did not tweet release %s %s',
                $message->getPackage(),
                $message->getVersion()
            ));
            return;
        }

        $message = str_replace(
            ['{package}',             '{version}',             '{url}'],
            [$message->getPackage(),  $message->getVersion(),  $message->getUrl()],
            self::TWEET_TEMPLATE
        );

        $response = $this->twitter->statusesUpdate($message);
        if ($response->isError()) {
            $this->logger->error(sprintf(
                'Error tweeting release %s %s: %s',
                $message->getPackage(),
                $message->getVersion(),
                implode("\n", $response->getErrors())
            ));
        }
    }

    private function prepareTwitterClient(): bool
    {
        $response = $this->twitter->accountVerifyCredentials();
        return $response->isSuccess();
    }
}
