<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\GitHub\Event\RegisterWebhook;
use App\GitHub\GitHubClient;
use App\Slack\Domain\SlashResponseMessage;
use App\Slack\SlackClientInterface;
use App\UrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

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
            return;
        }

        $message = new SlashResponseMessage();
        $message->setText(sprintf(
            'laminas-bot webhook for %s registered',
            $webhookRegistration->repo()
        ));

        $this->slack->sendWebhookMessage($webhookRegistration->responseUrl(), $message);
    }

    private function reportError(ResponseInterface $response, RegisterWebhook $webhookRegistration): void
    {
        $this->logger->error(sprintf(
            'Error registering laminas-bot webhook for %s: %s',
            $webhookRegistration->repo(),
            (string) $response->getBody()
        ));

        $message = new SlashResponseMessage();
        $message->setText(sprintf(
            '*Error registering laminas-bot webhook for %s*; ask Matthew to check the error logs for details',
            $webhookRegistration->repo()
        ));

        $this->slack->sendWebhookMessage($webhookRegistration->responseUrl(), $message);
    }
}
