<?php

declare(strict_types=1);

namespace App\Slack\Response;

use Psr\Http\Message\ResponseInterface;

use function json_decode;

class SlackResponse implements SlackResponseInterface
{
    /** @var string */
    private $body;

    /** @var ResponseInterface */
    private $response;

    /** @var array */
    private $payload;

    public static function createFromResponse(ResponseInterface $response): self
    {
        $body    = trim((string) $response->getBody());
        $payload = $body === 'ok'
            ? ['ok' => true]
            : json_decode($body, true);

        if (! is_array($payload)) {
            $payload = [];
        }

        $slackResponse = new self();
        $slackResponse->response = $response;
        $slackResponse->payload  = $payload;

        return $slackResponse;
    }

    public function isOk(): bool
    {
        return $this->payload['ok'] ?? false;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getError(): ?string
    {
        return $this->payload['error'] ?? null;
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
