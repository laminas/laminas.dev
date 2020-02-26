<?php

declare(strict_types=1);

namespace App\Slack;

use App\Slack\Method\ApiRequestInterface;
use App\Slack\Response\SlackResponse;
use App\Slack\Response\SlackResponseInterface;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

use function json_encode;
use function sprintf;

class SlackClient implements SlackClientInterface
{
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

    /** @see @web = new WebClient options.token */
    public function __construct(
        HttpClient $httpClient,
        string $token,
        ?LoggerInterface $logger = null
    ) {
        $this->httpClient     = $httpClient;
        $this->token          = $token;
        $this->logger         = $logger;
    }

    public function send(RequestInterface $request): SlackResponseInterface
    {
        // Send json request with auth token
        $response = $this->httpClient->send(
            $request->withHeader('Authorization', 'Bearer ' . $this->token)
        );

        $slackResponse = SlackResponse::createFromResponse($response);
        if ($this->logger && $slackResponse->isOk() !== true) {
            $this->logger->error('SlackClient: error sending message', [
                'code'  => $slackResponse->getStatusCode(),
                'error' => $slackResponse->getError() ?? 'unknown',
            ]);
        }

        return $slackResponse;
    }

    public function sendApiRequest(ApiRequestInterface $apiRequest): SlackResponseInterface
    {
        $endpoint = sprintf('https://slack.com/api/%s', $apiRequest->getEndpoint());

        /** @var RequestInterface $request */
        $request = new Request(
            $apiRequest->getMethod(),
            $endpoint,
            ['Content-Type' => 'application/json; charset=utf-8'],
            json_encode($apiRequest->toArray() ?? [])
        );

        return $this->send($request);
    }

    public function sendWebhookMessage(string $url, array $message): SlackResponseInterface
    {
        $request = new Request(
            'POST',
            $url,
            ['Content-Type' => 'application/json; charset=utf-8'],
            json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
        return $this->send($request);
    }
}
