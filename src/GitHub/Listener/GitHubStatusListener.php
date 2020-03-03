<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\GitHub\Event\GitHubStatus;
use App\Slack\Domain\Block;
use App\Slack\Domain\WebAPIMessage;
use App\Slack\SlackClientInterface;
use Assert\AssertionFailedException;
use GuzzleHttp\Client as HttpClient;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;
use Throwable;

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
        $pullRequest = $message->isForPullRequest()
            ? $this->fetchPullRequestData($message)
            : null;

        $notification = new WebAPIMessage();
        $notification->setChannel($this->channel);
        $notification->setText($message->getFallbackMessage($pullRequest));
        foreach ($message->getMessageBlocks($pullRequest) as $block) {
            $notification->addBlock(Block::create($block));
        }

        $this->slackClient->sendWebAPIMessage($notification);
    }

    private function fetchPullRequestData(GitHubStatus $status): ?PullRequest
    {
        $url = sprintf(
            '%s?repo:%s+%s',
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
            return null;
        }

        try {
            $pullRequest = new PullRequest(
                json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR)
            );
            $pullRequest->validate();
        } catch (AssertionFailedException $e) {
            return null;
        } catch (Throwable $e) {
            return null;
        }

        return $pullRequest;
    }
}
