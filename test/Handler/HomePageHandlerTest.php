<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\HomePageHandler;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class HomePageHandlerTest extends TestCase
{
    use ProphecyTrait;

    public function testReturnsHtmlResponse(): void
    {
        $contents = '<strong>Contents</strong>';

        $renderer = $this->prophesize(TemplateRendererInterface::class);
        $renderer
            ->render('app::home-page', Argument::type('array'))
            ->willReturn($contents);

        $body = $this->prophesize(StreamInterface::class);
        $body->write($contents)->shouldBeCalled();

        $response = $this->prophesize(ResponseInterface::class);
        $response->withHeader('Content-Type', 'text/html')->will([$response, 'reveal']);
        $response->getBody()->will([$body, 'reveal']);

        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory->createResponse(200)->will([$response, 'reveal']);

        $homePage = new HomePageHandler($renderer->reveal(), $responseFactory->reveal());

        $this->assertSame($response->reveal(), $homePage->handle(
            $this->prophesize(ServerRequestInterface::class)->reveal()
        ));
    }
}
