<?php

declare(strict_types=1);

namespace AppTest\Discourse\Middleware;

use App\Discourse\Middleware\VerificationMiddleware;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function hash_hmac;

class VerificationMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    /** StreamInterface|ObjectProphecy */
    private $body;

    /** @var RequestHandlerInterface|ObjectProphecy */
    private $handler;

    /** @var VerificationMiddleware */
    private $middleware;

    /** ServerRequestInterface|ObjectProphecy */
    private $request;

    /** ResponseInterface|ObjectProphecy */
    private $response;

    /** ProblemDetailsResponseFactory|ObjectProphecy */
    private $responseFactory;

    /** @var string */
    private $secret;

    public function setUp(): void
    {
        $this->secret          = 'mysecret';
        $this->request         = $this->prophesize(ServerRequestInterface::class);
        $this->response        = $this->prophesize(ResponseInterface::class)->reveal();
        $this->responseFactory = $this->prophesize(ProblemDetailsResponseFactory::class);
        $this->handler         = $this->prophesize(RequestHandlerInterface::class);
        $this->body            = $this->prophesize(StreamInterface::class);
        $this->middleware      = new VerificationMiddleware(
            $this->secret,
            $this->responseFactory->reveal()
        );
    }

    public function testReturns400ResponseWhenSignatureMissing(): void
    {
        $this->request->getHeaderLine('X-Discourse-Event-Signature')->willReturn('')->shouldBeCalled();
        $this->request->getBody()->shouldNotBeCalled();
        $this->responseFactory
             ->createResponse($this->request->reveal(), 400, 'No Discourse payload signature headers present')
             ->willReturn($this->response)
             ->shouldBeCalled();
        $this->handler->handle(Argument::any())->shouldNotBeCalled();

        $this->assertSame(
            $this->response,
            $this->middleware->process($this->request->reveal(), $this->handler->reveal())
        );
    }

    public function testReturns203ResponseIfComputationDoesNotMatchSignature(): void
    {
        $signature = hash_hmac('sha256', 'not the body text', $this->secret);
        $this->body->__toString()->willReturn('the actual body text')->shouldBeCalled();

        $this->request->getHeaderLine('X-Discourse-Event-Signature')->willReturn($signature)->shouldBeCalled();
        $this->request->getBody()->will([$this->body, 'reveal'])->shouldBeCalled();
        $this->responseFactory
            ->createResponse($this->request->reveal(), 203, 'Invalid or missing signature')
            ->willReturn($this->response)
            ->shouldBeCalled();
        $this->handler->handle(Argument::any())->shouldNotBeCalled();

        $this->assertSame(
            $this->response,
            $this->middleware->process($this->request->reveal(), $this->handler->reveal())
        );
    }

    public function testPassingHandlingToHandlerIfComputationMatchesSignature(): void
    {
        $signature = hash_hmac('sha256', 'the body text', $this->secret);
        $this->body->__toString()->willReturn('the body text')->shouldBeCalled();

        $this->request->getHeaderLine('X-Discourse-Event-Signature')->willReturn($signature)->shouldBeCalled();
        $this->request->getBody()->will([$this->body, 'reveal'])->shouldBeCalled();
        $this->responseFactory->createResponse(Argument::any())->shouldNotBeCalled();
        $this->handler
             ->handle(Argument::that([$this->request, 'reveal']))
             ->willReturn($this->response)
             ->shouldBeCalled();

        $this->assertSame(
            $this->response,
            $this->middleware->process($this->request->reveal(), $this->handler->reveal())
        );
    }

    public function testStripsLeadingSignatureTypePrefixPriorToSignatureComparison(): void
    {
        $signature = 'sha256=' . hash_hmac('sha256', 'the body text', $this->secret);
        $this->body->__toString()->willReturn('the body text')->shouldBeCalled();

        $this->request->getHeaderLine('X-Discourse-Event-Signature')->willReturn($signature)->shouldBeCalled();
        $this->request->getBody()->will([$this->body, 'reveal'])->shouldBeCalled();
        $this->responseFactory->createResponse(Argument::any())->shouldNotBeCalled();
        $this->handler
             ->handle(Argument::that([$this->request, 'reveal']))
             ->willReturn($this->response)
             ->shouldBeCalled();

        $this->assertSame(
            $this->response,
            $this->middleware->process($this->request->reveal(), $this->handler->reveal())
        );
    }
}
