<?php

declare(strict_types=1);

namespace App\Slack\Method;

/**
 * https://api.slack.com/methods/auth.test
 */
class AuthTest implements ApiRequestInterface
{
    /** @var string */
    private $method = 'POST';

    /** @var string */
    private $endpoint = 'auth.test';

    public function __construct()
    {
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
        return null;
    }
}
