<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\HomePageHandler;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class HomePageHandlerTest extends TestCase
{
    public function testReturnsHtmlResponse(): void
    {
        $response = (new HomePageHandler())->handle(
            $this->createMock(ServerRequestInterface::class)
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(
            'https://getlaminas.org',
            $response->getHeader('location')[0]
        );
    }
}
