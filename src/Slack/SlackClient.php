<?php

declare(strict_types=1);

namespace App\Slack;

use App\HttpClientInterface;
use App\Slack\Domain\MessageInterface;
use App\Slack\Domain\SlashResponseMessage;
use App\Slack\Domain\WebAPIMessage;
use App\Slack\Response\SlackResponse;
use App\Slack\Response\SlackResponseInterface;
use Exception;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

use function json_encode;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class SlackClient implements SlackClientInterface
{
    public const ENDPOINT_CHAT = 'https://slack.com/api/chat.postMessage';

    /**
     * Default flags for json_encode; value of:
     *
     * <code>
     * JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
     * </code>
     *
     * @const int
     */
    public const DEFAULT_JSON_FLAGS = 79;

    /** @var string */
    private $token;

    /** @var HttpClientInterface */
    private $httpClient;

    /** @var null|LoggerInterface */
    private $logger;

    /** @see @web = new WebClient options.token */
    public function __construct(
        HttpClientInterface $httpClient,
        string $token,
        ?LoggerInterface $logger = null
    ) {
        $this->httpClient = $httpClient;
        $this->token      = $token;
        $this->logger     = $logger;
    }

    public function send(RequestInterface $request): SlackResponseInterface
    {
        // Send json request with auth token
        $response = $this->httpClient->send(
            $request->withHeader('Authorization', 'Bearer ' . $this->token)
        );

        $slackResponse = SlackResponse::createFromResponse($response);
        if ($slackResponse->isOk() !== true) {
            $this->logErrorResponse($slackResponse);
        }

        return $slackResponse;
    }

    public function sendWebAPIMessage(WebAPIMessage $message): ?SlackResponseInterface
    {
        try {
            $message->validate();
        } catch (Exception $e) {
            $this->logInvalidMessage($message, $e);
            return null;
        }

        $request = $this->httpClient->createRequest('POST', self::ENDPOINT_CHAT)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('Accept', 'application/json');

        $request->getBody()->write(json_encode(
            $message->toArray(),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ));

        return $this->send($request);
    }

    public function sendWebhookMessage(string $url, SlashResponseMessage $message): ?SlackResponseInterface
    {
        if ($url === '') {
            $this->log('Unable to send webhook message; no URL provided', ['message' => $message->toArray()]);
            return null;
        }

        try {
            $message->validate();
        } catch (Exception $e) {
            $this->logInvalidMessage($message, $e);
            return null;
        }

        $request = $this->httpClient->createRequest('POST', $url)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('Accept', 'application/json');

        $request->getBody()->write(json_encode(
            $message->toArray(),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ));

        return $this->send($request);
    }

    private function log(string $message, array $context = []): void
    {
        if (! $this->logger) {
            return;
        }

        $this->logger->error($message, $context);
    }

    private function logInvalidMessage(MessageInterface $message, Exception $e): void
    {
        $this->log('SlackClient: invalid message provided', [
            'message' => $message->toArray(),
            'error'   => $e->getMessage(),
        ]);
    }

    private function logErrorResponse(SlackResponseInterface $response): void
    {
        $this->log('SlackClient: error sending message', [
            'code'  => $response->getStatusCode(),
            'error' => $response->getError() ?? 'unknown',
        ]);
    }
}
