<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Mezzio\Template\TemplateRendererInterface;

class ChatHandler implements RequestHandlerInterface
{
    /** @var TemplateRendererInterface */
    private $renderer;

    /** @var ResponseFactoryInterface */
    private $responseFactory;

    public function __construct(
        TemplateRendererInterface $renderer,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->renderer        = $renderer;
        $this->responseFactory = $responseFactory;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'text/html');
        $response->getBody()->write($this->renderer->render('app::chat', []));
        return $response;
    }
}
