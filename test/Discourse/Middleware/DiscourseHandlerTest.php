<?php

declare(strict_types=1);

namespace AppTest\Discourse\Middleware;

use App\Discourse\Event\DiscoursePost;
use App\Discourse\Middleware\DiscourseHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function date;

class DiscourseHandlerTest extends TestCase
{
    public function validPostIds(): iterable
    {
        yield 'null' => [null];
        yield 'first' => [1];
    }

    /** @dataProvider validPostIds */
    public function testDispatchesDiscoursePostWithValidIdAndReturnsEmpty202Response(?int $id): void
    {
        /** @var ServerRequestInterface|ObjectProphecy $request */
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('channel')->willReturn('qanda')->shouldBeCalled();

        $now = date('r');
        $request
            ->getParsedBody()
            ->willReturn([
                'post' => [
                    'topic_slug' => 'some-topic',
                    'topic_id'   => 42,
                    'id'         => $id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ])
            ->shouldBeCalled();

        $discourseUrl = 'https://discourse.laminas.dev';

        $response        = $this->prophesize(ResponseInterface::class)->reveal();
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory
            ->createResponse(202)
            ->willReturn($response)
            ->shouldBeCalled();

        $dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $dispatcher
            ->dispatch(Argument::that(function ($post) use ($id) {
                TestCase::assertInstanceOf(DiscoursePost::class, $post);
                TestCase::assertSame('#qanda', $post->getChannel());
                TestCase::assertSame(
                    sprintf('https://discourse.laminas.dev/t/some-topic/42/%d', $id ?: 1),
                    $post->getPostUrl()
                );
                TestCase::assertTrue($post->isValidForSlack());
                return $post;
            }))
            ->shouldBeCalled();

        $handler = new DiscourseHandler(
            $discourseUrl,
            $dispatcher->reveal(),
            $responseFactory->reveal()
        );

        $this->assertSame($response, $handler->handle($request->reveal()));
    }

    public function testDoesNotDispatchCommentPostButStillReturnsEmpty202Response(): void
    {
        /** @var ServerRequestInterface|ObjectProphecy $request */
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('channel')->willReturn('qanda')->shouldBeCalled();

        $now = date('r');
        $request
            ->getParsedBody()
            ->willReturn([
                'post' => [
                    'topic_slug' => 'some-topic',
                    'topic_id'   => 42,
                    'id'         => 4242,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ])
            ->shouldBeCalled();

        $discourseUrl = 'https://discourse.laminas.dev';

        $response        = $this->prophesize(ResponseInterface::class)->reveal();
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory
            ->createResponse(202)
            ->willReturn($response)
            ->shouldBeCalled();

        $dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $dispatcher
            ->dispatch(Argument::any())
            ->shouldNotBeCalled();

        $handler = new DiscourseHandler(
            $discourseUrl,
            $dispatcher->reveal(),
            $responseFactory->reveal()
        );

        $this->assertSame($response, $handler->handle($request->reveal()));
    }
}
