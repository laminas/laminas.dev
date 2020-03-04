<?php

declare(strict_types=1);

namespace AppTest\Slack\Middleware;

use App\Slack\Middleware\VerificationMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\ServerRequest;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;

class VerificationMiddlewareTest extends TestCase
{
    public function setUp(): void
    {
        $this->responseFactory = $this->prophesize(ProblemDetailsResponseFactory::class);
    }

    public function testVerificationIsSuccessful() : void
    {
        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withParsedBody([
                'token'       => 'gIkuvaNzQIHg97ATvDxqgjtO',
                'team_id'     => 'T0001',
                'team_domain' => 'example',
                'command'     => '/deploy',
                'text'        => '94070',
            ]);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler
            ->handle($request)
            ->shouldBeCalled()
            ->willReturn($this->prophesize(ResponseInterface::class));

        $middleware = new VerificationMiddleware('gIkuvaNzQIHg97ATvDxqgjtO', 'T0001', $this->responseFactory->reveal());
        $response   = $middleware->process($request, $handler->reveal());

        self::assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testVerificationTokenIsMissing() : void
    {
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withParsedBody([
                'team_id'     => 'T0001',
                'team_domain' => 'example',
                'command'     => '/deploy',
                'text'        => '94070',
            ]);

        $handler->handle($request)->shouldNotBeCalled();

        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $this->responseFactory
            ->createResponse($request, 400, 'Missing token')
            ->willReturn($response)
            ->shouldBeCalled();

        $middleware = new VerificationMiddleware('foo', 'bar', $this->responseFactory->reveal());
        $this->assertSame($response, $middleware->process($request, $handler->reveal()));
    }

    public function testInvalidToken() : void
    {
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withParsedBody([
                'token'       => 'gIkuvaNzQIHg97ATvDxqgjtO',
                'team_id'     => 'T0001',
                'team_domain' => 'example',
                'command'     => '/deploy',
                'text'        => '94070',
            ]);

        $handler->handle($request)->shouldNotBeCalled();

        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $this->responseFactory
            ->createResponse($request, 400, 'Invalid token')
            ->willReturn($response)
            ->shouldBeCalled();
        $middleware = new VerificationMiddleware('foo', 'bar', $this->responseFactory->reveal());
        $this->assertSame($response, $middleware->process($request, $handler->reveal()));
    }

    public function testTeamIdIsMissing() : void
    {
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withParsedBody([
                'token'       => 'gIkuvaNzQIHg97ATvDxqgjtO',
                'team_domain' => 'example',
                'command'     => '/deploy',
                'text'        => '94070',
            ]);

        $handler->handle($request)->shouldNotBeCalled();

        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $this->responseFactory
            ->createResponse($request, 400, 'Missing team id')
            ->willReturn($response)
            ->shouldBeCalled();

        $middleware = new VerificationMiddleware('gIkuvaNzQIHg97ATvDxqgjtO', 'bar', $this->responseFactory->reveal());
        $this->assertSame($response, $middleware->process($request, $handler->reveal()));
    }

    public function testInvalidTeamId() : void
    {
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withParsedBody([
                'token'       => 'gIkuvaNzQIHg97ATvDxqgjtO',
                'team_id'     => 'T0001',
                'team_domain' => 'example',
                'command'     => '/deploy',
                'text'        => '94070',
            ]);

        $handler->handle($request)->shouldNotBeCalled();

        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $this->responseFactory
            ->createResponse($request, 400, 'Invalid team id')
            ->willReturn($response)
            ->shouldBeCalled();

        $middleware = new VerificationMiddleware('gIkuvaNzQIHg97ATvDxqgjtO', 'bar', $this->responseFactory->reveal());
        $this->assertSame($response, $middleware->process($request, $handler->reveal()));
    }
}
