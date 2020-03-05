<?php

declare(strict_types=1);

namespace App\GitHub;

use App\HttpClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use function sprintf;

class GitHubClient implements HttpClientInterface
{
    /** @var HttpClientInterface */
    private $httpClient;

    /** @var string */
    private $token;

    public function __construct(
        string $token,
        HttpClientInterface $httpClient
    ) {
        $this->token      = $token;
        $this->httpClient = $httpClient;
    }

    public function send(RequestInterface $request): ResponseInterface
    {
        return $this->httpClient->send($request);
    }

    /** @param string|UriInterface $url */
    public function createRequest(string $method, $url): RequestInterface
    {
        return $this->httpClient->createRequest($method, $url)
            ->withHeader('Authorization', sprintf('token %s', $this->token))
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/json');
    }
}
