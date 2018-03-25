<?php

declare(strict_types=1);

namespace App\Slack\Method;

/**
 * https://api.slack.com/methods/auth.test
 */
class ApiTest implements ApiRequestInterface
{
    /** @var string */
    private $method = 'POST';

    /** @var string */
    private $endpoint = 'api.test';

    /** @var array|null */
    private $payload;

    public function __construct(?array $payload = null)
    {
        $this->payload = $payload;
    }

    public function getMethod() : string
    {
        return $this->method;
    }

    public function getEndpoint() : string
    {
        return $this->endpoint;
    }

    public function toArray() : ?array
    {
        return $this->payload;
    }
}
