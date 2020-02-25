<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

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
                'blocks'        => [
                    [
                        'type'     => 'context',
                        'elements' => [
                            [
                                'type'      => 'image',
                                'image_url' => 'https://getlaminas.org/images/logo/trademark-laminas-144x144.png',
                                'alt_text'  => 'Laminas Bot',
                            ],
                            [
                                'type' => 'mrkdwn',
                                'text' => 'Laminas Bot',
                            ],
                        ],
                    ],
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => $text,
                        ],
                    ],
                ],
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));

        return $this->responseFactory->createResponse($status)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($body);
    }

    public function createUnauthorizedResponse(): ResponseInterface
    {
        return $this->createResponse('*You are not authorized to perform this action*');
    }
}
