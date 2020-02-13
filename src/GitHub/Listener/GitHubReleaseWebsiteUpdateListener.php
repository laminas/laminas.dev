<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\GitHub\Event\GitHubRelease;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Stream;
use Psr\Log\LoggerInterface;

class GitHubReleaseWebsiteUpdateListener
{
    private const DEFAULT_RELEASE_API_URL = 'https://getlaminas.org/api/release';

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $releaseApiUrl;

    /** @var string */
    private $token;

    public function __construct(
        LoggerInterface $logger,
        string $token,
        string $releaseApiUrl = self::DEFAULT_RELEASE_API_URL
    ) {
        $this->logger        = $logger;
        $this->token         = $token;
        $this->releaseApiUrl = $releaseApiUrl;
    }

    public function __invoke(GitHubRelease $message): void
    {
        if (! $message->isPublished()) {
            return;
        }

        $payload = [
            'package'          => $message->getPackage(),
            'version'          => $message->getVersion(),
            'url'              => $message->getUrl(),
            'changelog'        => $message->getChangelog(),
            'publication_date' => $message->getPublicationDate(),
            'author_name'      => $message->getAuthorName(),
            'author_url'       => $message->getAuthorUrl(),
        ];

        $client = new Client();

        $body    = new Stream(fopen('php://temp', 'rw'));
        $body->write(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $request = new Request('POST', $this->releaseApiUrl);
        $request = $request
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json')
            ->withBody($body);

        $response = $client->send($request);

        if ($response->getStatusCode() >= 400) {
            $this->logger->error(sprintf(
                'Error notifying %s of new release (%s %s): %s',
                $this->releaseApiUrl,
                $message->getPackage(),
                $message->getVersion(),
                (string) $response->getBody()
            ));
        }
    }
}
