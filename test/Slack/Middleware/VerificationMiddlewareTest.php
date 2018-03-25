<?php

declare(strict_types=1);

namespace AppTest\Slack\Middleware;

use App\Slack\Middleware\VerificationMiddleware;
use DomainException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\ServerRequest;

class VerificationMiddlewareTest extends TestCase
{
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

        $middleware = new VerificationMiddleware('gIkuvaNzQIHg97ATvDxqgjtO', 'T0001');
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

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Missing token');

        $middleware = new VerificationMiddleware('foo', 'bar');
        $middleware->process($request, $handler->reveal());
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

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid token');

        $middleware = new VerificationMiddleware('foo', 'bar');
        $middleware->process($request, $handler->reveal());
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

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Missing team id');

        $middleware = new VerificationMiddleware('gIkuvaNzQIHg97ATvDxqgjtO', 'bar');
        $middleware->process($request, $handler->reveal());
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

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid team id');

        $middleware = new VerificationMiddleware('gIkuvaNzQIHg97ATvDxqgjtO', 'bar');
        $middleware->process($request, $handler->reveal());
    }
}
