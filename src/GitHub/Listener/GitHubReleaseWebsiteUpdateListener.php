<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\GitHub\Event\GitHubRelease;
use GuzzleHttp\Client as HttpClient;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;

use function json_encode;
use function sprintf;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class GitHubReleaseWebsiteUpdateListener
{
    private const DEFAULT_RELEASE_API_URL = 'https://getlaminas.org/api/release';

    /** @var HttpClient */
    private $httpClient;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $releaseApiUrl;

    /** @var RequestFactoryInterface */
    private $requestFactory;

    /** @var string */
    private $token;

    public function __construct(
        HttpClient $client,
        RequestFactoryInterface $requestFactory,
        LoggerInterface $logger,
        string $token,
        string $releaseApiUrl = self::DEFAULT_RELEASE_API_URL
    ) {
        $this->httpClient     = $client;
        $this->requestFactory = $requestFactory;
        $this->logger         = $logger;
        $this->token          = $token;
        $this->releaseApiUrl  = $releaseApiUrl;
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

        $request = $this->requestFactory->createRequest('POST', $this->releaseApiUrl)
            ->withHeader('Authorization', sprintf('token %s', $this->token))
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json');
        $request->getBody()
            ->write(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $response = $this->httpClient->send($request);

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
