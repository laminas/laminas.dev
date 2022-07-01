<?php

declare(strict_types=1);

namespace AppTest\Slack\SlashCommand;

use App\Slack\Event\TwitterReply;
use App\Slack\SlashCommand\AuthorizedUserListInterface;
use App\Slack\SlashCommand\SlashCommandRequest;
use App\Slack\SlashCommand\SlashCommandResponseFactory;
use App\Slack\SlashCommand\TwitterReplyCommand;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;

use function str_repeat;

class TwitterReplyCommandTest extends TestCase
{
    use ProphecyTrait;

    /** @var AuthorizedUserListInterface|ObjectProphecy */
    private $acl;

    /** @var TwitterReplyCommand */
    private $command;

    /** @var EventDispatcherInterface|ObjectProphecy */
    private $dispatcher;

    /** @var SlashCommandRequest|ObjectProphecy */
    private $request;

    /** @var SlashCommandResponseFactory|ObjectProphecy */
    private $responseFactory;

    public function setUp(): void
    {
        $this->acl             = $this->prophesize(AuthorizedUserListInterface::class);
        $this->dispatcher      = $this->prophesize(EventDispatcherInterface::class);
        $this->request         = $this->prophesize(SlashCommandRequest::class);
        $this->responseFactory = $this->prophesize(SlashCommandResponseFactory::class);
        $this->command         = new TwitterReplyCommand(
            $this->responseFactory->reveal(),
            $this->dispatcher->reveal()
        );
    }

    public function testValidationFailsIfUserNotInAcl(): void
    {
        $userId   = 'user-id';
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $this->request->userId()->willReturn($userId)->shouldBeCalled();
        $this->acl->isAuthorized($userId)->willReturn(false)->shouldBeCalled();
        $this->responseFactory->createUnauthorizedResponse()->willReturn($response)->shouldBeCalled();

        $this->assertSame(
            $response,
            $this->command->validate($this->request->reveal(), $this->acl->reveal())
        );
    }

    public function messagesWithInvalidReplyUrls(): iterable
    {
        yield 'empty'                    => [''];
        yield 'invalid url'              => ['not-a-url'];
        yield 'invalid url with message' => ['not-a-url with a message'];
    }

    /** @dataProvider messagesWithInvalidReplyUrls */
    public function testValidationFailsIfUrlIsInvalid(string $text): void
    {
        $userId   = 'user-id';
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $this->request->userId()->willReturn($userId)->shouldBeCalled();
        $this->request->text()->willReturn($text)->shouldBeCalled();
        $this->acl->isAuthorized($userId)->willReturn(true)->shouldBeCalled();
        $this->responseFactory
            ->createResponse(Argument::containingString('valid URL'))
            ->willReturn($response)
            ->shouldBeCalled();

        $this->assertSame(
            $response,
            $this->command->validate($this->request->reveal(), $this->acl->reveal())
        );
    }

    public function messagesWithInvalidLengths(): iterable
    {
        $replyUrl = 'https://twitter.com/getlaminas/status/1239539812941651968';
        yield 'empty'      => [$replyUrl];
        yield 'whitespace' => [$replyUrl . '  '];
        yield 'too long'   => [$replyUrl . ' ' . str_repeat('abcd ', 57)]; // 285 characters
    }

    /** @dataProvider messagesWithInvalidLengths */
    public function testValidationFailsIfMessageLengthIsOutOfRange(string $text): void
    {
        $userId   = 'user-id';
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $this->request->userId()->willReturn($userId)->shouldBeCalled();
        $this->request->text()->willReturn($text)->shouldBeCalled();
        $this->acl->isAuthorized($userId)->willReturn(true)->shouldBeCalled();
        $this->responseFactory
            ->createResponse(Argument::containingString('at least 1'))
            ->willReturn($response)
            ->shouldBeCalled();

        $this->assertSame(
            $response,
            $this->command->validate($this->request->reveal(), $this->acl->reveal())
        );
    }

    public function testValidationSucceedsWhenBothUrlAndMessageArePresent(): void
    {
        $userId  = 'user-id';
        $message = 'https://twitter.com/getlaminas/status/1239539812941651968 This is the message';

        $this->request->userId()->willReturn($userId)->shouldBeCalled();
        $this->request->text()->willReturn($message)->shouldBeCalled();
        $this->acl->isAuthorized($userId)->willReturn(true)->shouldBeCalled();
        $this->responseFactory
            ->createResponse(Argument::any())
            ->shouldNotBeCalled();

        $this->assertNull(
            $this->command->validate($this->request->reveal(), $this->acl->reveal())
        );
    }

    public function testDispatchCreatesAndDispatchesTwitterReplyEventAndReturnsNull(): void
    {
        $message     = 'https://twitter.com/getlaminas/status/1239539812941651968 This is the message';
        $responseUrl = 'http://localhost:9000/api/slack';

        $this->request->text()->willReturn($message)->shouldBeCalled();
        $this->request->responseUrl()->willReturn($responseUrl)->shouldBeCalled();

        // phpcs:disable SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.MissingVariable
        $this->dispatcher
            ->dispatch(Argument::that(function ($event) use ($responseUrl) {
                TestCase::assertInstanceOf(TwitterReply::class, $event);
                /** @var TwitterReply $event */
                TestCase::assertSame('https://twitter.com/getlaminas/status/1239539812941651968', $event->replyUrl());
                TestCase::assertSame('This is the message', $event->message());
                TestCase::assertSame($responseUrl, $event->responseUrl());

                return $event;
            }))
            ->shouldBeCalled();
        // phpcs:enable SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.MissingVariable

        $this->responseFactory->createResponse(Argument::any())->shouldNotBeCalled();

        $this->assertNull($this->command->dispatch($this->request->reveal()));
    }
}
