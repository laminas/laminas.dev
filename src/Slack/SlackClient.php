<?php

declare(strict_types=1);

namespace App\Slack;

use App\Slack\Domain\MessageInterface;
use App\Slack\Domain\SlashResponseMessage;
use App\Slack\Domain\WebAPIMessage;
use App\Slack\Response\SlackResponse;
use App\Slack\Response\SlackResponseInterface;
use Exception;
use GuzzleHttp\Client as HttpClient;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

use function json_encode;

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

    /** @var HttpClient */
    private $httpClient;

    /** @var null|LoggerInterface */
    private $logger;

    /** RequestFactoryInterface */
    private $requestFactory;

    /** @see @web = new WebClient options.token */
    public function __construct(
        HttpClient $httpClient,
        string $token,
        RequestFactoryInterface $requestFactory,
        ?LoggerInterface $logger = null
    ) {
        $this->httpClient     = $httpClient;
        $this->token          = $token;
        $this->requestFactory = $requestFactory;
        $this->logger         = $logger;
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

        $request = $this->requestFactory->createRequest('POST', self::ENDPOINT_CHAT)
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
        try {
            $message->validate();
        } catch (Exception $e) {
            $this->logInvalidMessage($message, $e);
            return null;
        }

        $request = $this->requestFactory->createRequest('POST', $url)
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
            'error'   => $e->getMessage()
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
