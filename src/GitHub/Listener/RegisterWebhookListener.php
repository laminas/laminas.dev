<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\GitHub\Event\RegisterWebhook;
use App\GitHub\GitHubClient;
use App\Slack\SlackClientInterface;
use App\UrlHelper;
use Psr\Http\Message\ResponseInterface;

class RegisterWebhookListener
{
    /** @var Client */
    private $githubClient;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $secret;

    /** @var SlackClientInterface */
    private $slack;

    /** @var UrlHelper */
    private $url;

    public function __construct(
        string $secret,
        GitHubClient $githubClient,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
        SlackClientInterface $slack
    ) {
        $this->secret         = $secret;
        $this->githubClient   = $githubClient;
        $this->url            = $urlHelper;
        $this->logger         = $logger;
        $this->slack          = $slack;
    }

    public function __invoke(RegisterWebhook $webhookRegistration)
    {
        $request = $this->githubClient->createRequest(
            'POST',
            sprintf('https://api.github.com/repos/%s/hooks', $webhookRegistration->repo())
        );
        $request->getBody()->write(json_encode(
            [
                'name'   => 'web',
                'config' => [
                    'url'          => $this->url->generate('api.github'),
                    'content_type' => 'json',
                    'secret'       => $this->secret,
                ],
                'events' => [
                    'issues',
                    'issue_comment',
                    'pull_request',
                    'release',
                    'status',
                ],
                'active' => true,
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));

        $response = $this->githubClient->send($request);

        if ($response->getStatusCode() !== 201) {
            $this->reportError($response, $webhookRegistration);
        }

        $this->slack->sendWebhookMessage($webhookRegistration->responseUrl(), [
            'response_type' => 'ephemeral',
            'mrkdwn'        => true,
            'text'          => sprintf(
                'laminas-bot webhook for %s registered',
                $webhookRegistration->repo()
            ),
        ]);
    }

    private function reportError(ResponseInterface $response, RegisterWebhook $webhookRegistration): void
    {
        $this->logger->error(sprintf(
            'Error attempting to register laminas-bot webook for %s: %s',
            $webhookRegistration->repo(),
            (string) $response->getBody()
        ));

        $this->slack->sendWebhookMessage($webhookRegistration->responseUrl(), [
            'response_type' => 'ephemeral',
            'mrkdwn'        => true,
            'text'          => sprintf(
                '*Error registering laminas-bot webhook for %s*; ask Matthew to check the error logs for details',
                $webhookRegistration->repo()
            ),
        ]);
    }
}
