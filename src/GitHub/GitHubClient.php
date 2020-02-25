<?php

declare(strict_types=1);

namespace App\GitHub;

use GuzzleHttp\Client as HttpClient;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class GitHubClient
{
    /** @var HttpClient */
    private $httpClient;

    /** @var RequestFactoryInterface */
    private $requestFactory;

    /** @var string */
    private $token;

    public function __construct(
        string $token,
        RequestFactoryInterface $requestFactory,
        HttpClient $httpClient
    )
    {
        $this->token          = $token;
        $this->requestFactory = $requestFactory;
        $this->httpClient     = $httpClient;
    }

    public function createRequest(string $method, string $url): RequestInterface
    {
        return $this->requestFactory->createRequest($method, $url)
            ->withHeader('Authorization', sprintf('token %s', $this->token))
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/json');
    }

    public function send(RequestInterface $request): ResponseInterface
    {
        return $this->httpClient->send($request);
    }
}
