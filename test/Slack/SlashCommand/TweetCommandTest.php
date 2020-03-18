<?php

declare(strict_types=1);

namespace AppTest\Slack\SlashCommand;

use App\Slack\Event\Tweet;
use App\Slack\SlashCommand\AuthorizedUserListInterface;
use App\Slack\SlashCommand\SlashCommandRequest;
use App\Slack\SlashCommand\SlashCommandResponseFactory;
use App\Slack\SlashCommand\TweetCommand;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;

use function sprintf;
use function str_repeat;

class TweetCommandTest extends TestCase
{
    /** @var AuthorizedUserListInterface|ObjectProphecy */
    private $acl;

    /** @var TweetCommand */
    private $command;

    /** @var EventDispatcherInterface|ObjectProphecy */
    private $dispatcher;

    /** @var SlashCommandRequest|ObjectProphecy */
    private $request;

    /** @var SlashCommandResponseFactory|ObjectProphecy */
    private $responseFactory;

    public function setUp(): void
    {
        $this->request         = $this->prophesize(SlashCommandRequest::class);
        $this->acl             = $this->prophesize(AuthorizedUserListInterface::class);
        $this->responseFactory = $this->prophesize(SlashCommandResponseFactory::class);
        $this->dispatcher      = $this->prophesize(EventDispatcherInterface::class);
        $this->command         = new TweetCommand(
            $this->responseFactory->reveal(),
            $this->dispatcher->reveal()
        );
    }

    public function testValidationFailsIfUserIsNotAuthorized(): void
    {
        $userId   = 'some-user';
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $this->request->userId()->willReturn($userId)->shouldBeCalled();
        $this->acl->isAuthorized($userId)->willReturn(false)->shouldBeCalled();
        $this->responseFactory->createUnauthorizedResponse()->willReturn($response)->shouldBeCalled();

        $this->assertSame($response, $this->command->validate(
            $this->request->reveal(),
            $this->acl->reveal()
        ));
    }

    public function invalidMessageLengths(): iterable
    {
        $media = 'media:https://getlaminas.org/images/logo/trademark-laminas-144x144.png';
        yield 'empty'               => [''];
        yield 'media-only'          => [$media];
        yield 'too-long'            => [str_repeat('a', 281)];
        yield 'too-long-with-media' => [$media . ' ' . str_repeat('a', 281)];
    }

    /** @dataProvider invalidMessageLengths */
    public function testValidationFailsIfMessageLengthIsOutOfRange(string $message): void
    {
        $userId   = 'some-user';
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $this->request->userId()->willReturn($userId)->shouldBeCalled();
        $this->acl->isAuthorized($userId)->willReturn(true)->shouldBeCalled();
        $this->request->text()->willReturn($message)->shouldBeCalled();

        $this->responseFactory
            ->createResponse(Argument::containingString('at least 1 and no more than 280'))
            ->willReturn($response)
            ->shouldBeCalled();

        $this->assertSame($response, $this->command->validate(
            $this->request->reveal(),
            $this->acl->reveal()
        ));
    }

    public function testValidationFailsIfMediaProvidedIsAnInvalidUrl(): void
    {
        $userId   = 'some-user';
        $message  = 'media:not-a-URL the message';
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $this->request->userId()->willReturn($userId)->shouldBeCalled();
        $this->acl->isAuthorized($userId)->willReturn(true)->shouldBeCalled();
        $this->request->text()->willReturn($message)->shouldBeCalled();

        $this->responseFactory
            ->createResponse(Argument::containingString('MUST be a valid URL'))
            ->willReturn($response)
            ->shouldBeCalled();

        $this->assertSame($response, $this->command->validate(
            $this->request->reveal(),
            $this->acl->reveal()
        ));
    }

    public function validMessages(): iterable
    {
        $media = 'media:https://getlaminas.org/images/logo/trademark-laminas-144x144.png';
        yield 'message-only'                 => ['this is a message'];
        yield 'message-with-media-preceding' => [$media . ' this is a message'];
        yield 'message-with-media-following' => ['this is a message ' . $message];
    }

    /** @dataProvider validMessages */
    public function testValidationReturnsNullIfMessageIsValid(string $message): void
    {
        $userId = 'some-user';

        $this->request->userId()->willReturn($userId)->shouldBeCalled();
        $this->acl->isAuthorized($userId)->willReturn(true)->shouldBeCalled();
        $this->request->text()->willReturn($message)->shouldBeCalled();

        $this->responseFactory
            ->createUnauthorizedResponse()
            ->shouldNotBeCalled();
        $this->responseFactory
            ->createResponse(Argument::any())
            ->shouldNotBeCalled();

        $this->assertNull($this->command->validate(
            $this->request->reveal(),
            $this->acl->reveal()
        ));
    }

    public function testDispatchCreatesAndDispatchesTweetAndReturnsResponse(): void
    {
        $media       = 'https://getlaminas.org/images/logo/trademark-laminas-144x144.png';
        $message     = 'Some message to tweet';
        $responseUrl = 'https://localhost:9000/api/slack';
        $response    = $this->prophesize(ResponseInterface::class)->reveal();

        $this->request->text()->willReturn(sprintf('media:%s %s', $media, $message))->shouldBeCalled();
        $this->request->responseUrl()->willReturn($responseUrl)->shouldBeCalled();

        $this->dispatcher
             ->dispatch(Argument::that(function ($tweet) use ($media, $message, $responseUrl) {
                 TestCase::assertInstanceOf(Tweet::class, $tweet);
                 /** @var Tweet $tweet */
                 TestCase::assertSame($media, $tweet->media());
                 TestCase::assertSame($message, $tweet->message());
                 TestCase::assertSame($responseUrl, $tweet->responseUrl());

                 return $tweet;
             }))
             ->shouldBeCalled();

        $this->responseFactory
            ->createResponse(Argument::containingString('Tweet queued'))
            ->willReturn($response)
            ->shouldBeCalled();

        $this->assertSame($response, $this->command->dispatch($this->request->reveal()));
    }
}
