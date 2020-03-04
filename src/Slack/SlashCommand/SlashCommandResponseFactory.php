<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use App\Slack\Domain\ContextBlock;
use App\Slack\Domain\ImageElement;
use App\Slack\Domain\SectionBlock;
use App\Slack\Domain\SlashResponseMessage;
use App\Slack\Domain\TextObject;
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
        $context = new ContextBlock();
        $context->addElement(new ImageElement(
            'https://getlaminas.org/images/logo/trademark-laminas-144x144.png',
            'Laminas Bot'
        ));
        $context->addElement(new TextObject('Laminas Bot'));

        $textSection = new SectionBlock();
        $textSection->setText(new TextObject($text));

        $message = new SlashResponseMessage();
        $message->addBlock($context);
        $message->addBlock($textSection);

        $body = $this->streamFactory->createStream(json_encode(
            $message->toArray(),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));

        return $this->responseFactory->createResponse($status)
            ->withHeader('Accept', 'application/json; charset=utf-8')
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody($body);
    }

    public function createUnauthorizedResponse(): ResponseInterface
    {
        return $this->createResponse('*You are not authorized to perform this action*');
    }
}
