<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\GitHub\Event\DocsBuildAction;
use App\GitHub\GitHubClient;
use App\Slack\Domain\SlashResponseMessage;
use App\Slack\SlackClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

use function json_encode;
use function sprintf;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class DocsBuildActionListener
{
    /** @var GitHubClient */
    private $githubClient;

    /** @var LoggerInterface */
    private $logger;

    /** @var SlackClientInterface */
    private $slack;

    public function __construct(
        GitHubClient $githubClient,
        LoggerInterface $logger,
        SlackClientInterface $slack
    ) {
        $this->githubClient = $githubClient;
        $this->logger       = $logger;
        $this->slack        = $slack;
    }

    public function __invoke(DocsBuildAction $docsAction): void
    {
        $request = $this->githubClient->createRequest(
            'POST',
            sprintf('https://api.github.com/repos/%s/dispatches', $docsAction->repo())
        );
        $request->getBody()->write(json_encode(
            ['event_type' => 'docs-build'],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));

        $response = $this->githubClient->send($request);
        if ($response->getStatusCode() !== 204) {
            $this->reportError($response, $docsAction);
            return;
        }
    }

    private function reportError(ResponseInterface $response, DocsBuildAction $docsAction): void
    {
        $this->logger->error(sprintf(
            'Error attempting to trigger documentation build for %s: %s',
            $docsAction->repo(),
            (string) $response->getBody()
        ));

        $message = new SlashResponseMessage();
        $message->setText(sprintf(
            '*Error queueing documentation build for %s*; ask Matthew to check the error logs for details',
            $docsAction->repo()
        ));

        $this->slack->sendWebhookMessage($docsAction->responseUrl(), $message);
    }
}
