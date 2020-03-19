<?php

declare(strict_types=1);

namespace AppTest\Twitter;

use App\Twitter\VerificationMiddleware;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class VerificationMiddlewareTest extends TestCase
{
    /** @var RequestHandlerInterface|ObjectProphecy */
    private $handler;

    /** @var VerificationMiddleware */
    private $middleware;

    /** @var ServerRequestInterface|ObjectProphecy */
    private $request;

    /** @var ResponseFactoryInterface|ObjectProphecy */
    private $responseFactory;

    /** @var string */
    private $token;

    public function setUp(): void
    {
        $this->token           = 'verification-token';
        $this->responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $this->request         = $this->prophesize(ServerRequestInterface::class);
        $this->handler         = $this->prophesize(RequestHandlerInterface::class);
        $this->middleware      = new VerificationMiddleware(
            $this->token,
            $this->responseFactory->reveal()
        );
    }

    public function invalidTokens(): iterable
    {
        yield 'null' => [null];
        yield 'mismatch' => ['not-the-token'];
    }

    /**
     * @dataProvider invalidTokens
     * @param mixed $tokenValue
     */
    public function testReturns401ResponseOnTokenMismatch($tokenValue): void
    {
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $this->request->getAttribute('token')->willReturn($tokenValue)->shouldBeCalled();
        $this->request->getParsedBody()->shouldNotBeCalled();
        $this->responseFactory->createResponse(401)->willReturn($response)->shouldBeCalled();
        $this->handler->handle($this->request->reveal())->shouldNotBeCalled();

        $this->assertSame($response, $this->middleware->process(
            $this->request->reveal(),
            $this->handler->reveal()
        ));
    }

    public function invalidPayloads(): iterable
    {
        yield 'empty' => [[]];

        yield 'object' => [(object) []];

        yield 'text-only' => [['text' => 'some message']];

        yield 'text-and-malformed-url' => [['text' => 'some message', 'url' => 'not-a real URL']];

        yield 'text-and-url-only' => [
            [
                'text' => 'some message',
                'url'  => 'https://twitter.com/getlaminas/status/1240620908454326274',
            ],
        ];
    }

    /**
     * @dataProvider invalidPayloads
     * @param mixed $payload
     */
    public function testReturns400ResponseIfBodyContentDoesNotValidate($payload): void
    {
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $this->request->getAttribute('token')->willReturn($this->token)->shouldBeCalled();
        $this->request->getParsedBody()->willReturn($payload)->shouldBeCalled();
        $this->responseFactory->createResponse(400)->willReturn($response)->shouldBeCalled();
        $this->handler->handle($this->request->reveal())->shouldNotBeCalled();

        $this->assertSame($response, $this->middleware->process(
            $this->request->reveal(),
            $this->handler->reveal()
        ));
    }

    public function testProxiesToHandlerToReturnResponseIfVerificationSucceeds(): void
    {
        $payload  = [
            'text'      => 'This is the tweet text',
            'url'       => 'https://twitter.com/getlaminas/status/1240620908454326274',
            'timestamp' => '2020-03-19T11:29:12-05:00',
        ];
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $this->request->getAttribute('token')->willReturn($this->token)->shouldBeCalled();
        $this->request->getParsedBody()->willReturn($payload)->shouldBeCalled();
        $this->responseFactory->createResponse(Argument::any())->shouldNotBeCalled();
        $this->handler->handle($this->request->reveal())->willReturn($response)->shouldBeCalled();

        $this->assertSame($response, $this->middleware->process(
            $this->request->reveal(),
            $this->handler->reveal()
        ));
    }
}
