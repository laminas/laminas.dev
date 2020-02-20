<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\GitHub\Event\GitHubStatus;
use App\Slack\Domain\Attachment;
use App\Slack\Domain\AttachmentColor;
use App\Slack\Method\ChatPostMessage;
use App\Slack\SlackClientInterface;
use Assert\AssertionFailedException;
use GuzzleHttp\Client as HttpClient;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;

use function sprintf;

class GitHubStatusListener
{
    private const GITHUB_ISSUE_SEARCH_URI = 'https://api.github.com/search/issues';

    /** @var string */
    private $channel;

    /** @var HttpClient */
    private $httpClient;

    /** @var LoggerInterface */
    private $logger;

    /** @var RequestFactoryInterface */
    private $requestFactory;

    /** @var SlackClientInterface */
    private $slackClient;

    public function __construct(
        string $channel,
        SlackClientInterface $slackClient,
        HttpClient $httpClient,
        RequestFactoryInterface $requestFactory,
        LoggerInterface $logger
    ) {
        $this->channel        = $channel;
        $this->slackClient    = $slackClient;
        $this->httpClient     = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->logger         = $logger;
    }

    public function __invoke(GitHubStatus $message) : void
    {
        $notification = new ChatPostMessage($this->channel);
        $notification->addAttachment(new Attachment(
            $message->isForPullRequest()
                ? $this->fetchPullRequestData($message)
                : $message->getMessagePayload()
        ));

        $this->slackClient->sendApiRequest($notification);
    }

    /**
     * @return array array<string,mixed>
     */
    private function fetchPullRequestData(GitHubStatus $status): array
    {
        $url = sprintf(
            '%?repo:%s+%s',
            self::GITHUB_ISSUE_SEARCH_URI,
            $status->getRepository(),
            $status->getCommitIdentifier()
        );

        $response = $this->httpClient->send(
            $this->requestFactory->createRequest('GET', $url)
                ->withHeader('Accept', 'application/json')
        );

        if ($response->getStatusCode() !== 200) {
            $this->logger->error(sprintf(
                'Error fetching pull request details for %s@%s (%s): %s',
                $status->getRepository(),
                $status->getBranch(),
                $status->getCommitIdentifier(),
                (string) $response->getBody()
            ));
            return $status->getMessagePayload();
        }

        $pullRequest = new PullRequest(
            json_decode((string) $response->getBody(), true)
        );
        try {
            $pullRequest->validate();
        } catch (AssertionFailedException $e) {
            return $status->getMessagePayload();
        }

        return $status->prepareMessagePayloadForPullRequestStatus($pullRequest);
    }
}
