<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use Laminas\Feed\Reader\Http\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;

class SlashCommandResponseFactory
{
    /** @var ResponseFactoryInterface */
    private $responseFactory;

    /** @var StreamFactoryInterface */
    private $streamFactory;

    public function __construct(ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory)
    {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
    }

    public function createResponse(string $text, int $status = 200): ResponseInterface
    {
        $body = $this->streamFactory->createStream(json_encode(
            [
                'response_type' => 'ephemeral',
                'text'          => $text,
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));

        return $this->responseFactory->createResponse($status)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($body);
    }
}
