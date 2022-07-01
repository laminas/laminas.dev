<?php

declare(strict_types=1);

namespace AppTest\Slack\SlashCommand;

use App\Slack\Event\Retweet;
use App\Slack\SlashCommand\AuthorizedUserListInterface;
use App\Slack\SlashCommand\RetweetCommand;
use App\Slack\SlashCommand\SlashCommandRequest;
use App\Slack\SlashCommand\SlashCommandResponseFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;

class RetweetCommandTest extends TestCase
{
    /** @var AuthorizedUserListInterface|ObjectProphecy */
    private $acl;

    /** @var RetweetCommand */
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
        $this->request         = $this->prophesize(SlashCommandRequest::class);
        $this->responseFactory = $this->prophesize(SlashCommandResponseFactory::class);
        $this->dispatcher      = $this->prophesize(EventDispatcherInterface::class);
        $this->command         = new RetweetCommand(
            $this->responseFactory->reveal(),
            $this->dispatcher->reveal()
        );
    }

    public function testValidationFailsIfUserNotInAcl(): void
    {
        $userId   = 'some-user-id';
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $this->request->userId()->willReturn($userId)->shouldBeCalled();
        $this->acl->isAuthorized($userId)->willReturn(false)->shouldBeCalled();
        $this->responseFactory->createUnauthorizedResponse()->willReturn($response)->shouldBeCalled();

        $this->assertSame(
            $response,
            $this->command->validate($this->request->reveal(), $this->acl->reveal())
        );
    }

    public function testValidationFailsIfNotAUrl(): void
    {
        $userId   = 'some-user-id';
        $url      = 'not-a-url';
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $this->request->userId()->willReturn($userId)->shouldBeCalled();
        $this->request->text()->willReturn($url)->shouldBeCalled();
        $this->acl->isAuthorized($userId)->willReturn(true)->shouldBeCalled();
        $this->responseFactory
            ->createResponse('Retweet URL MUST be a valid URL pointing to a tweet.')
            ->willReturn($response)
            ->shouldBeCalled();

        $this->assertSame(
            $response,
            $this->command->validate($this->request->reveal(), $this->acl->reveal())
        );
    }

    public function testValidationFailsIfNotATweetUrl(): void
    {
        $userId   = 'some-user-id';
        $url      = 'http://localhost:9000/is-a-url';
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $this->request->userId()->willReturn($userId)->shouldBeCalled();
        $this->request->text()->willReturn($url)->shouldBeCalled();
        $this->acl->isAuthorized($userId)->willReturn(true)->shouldBeCalled();
        $this->responseFactory
            ->createResponse('Retweet URL MUST be a valid URL pointing to a tweet.')
            ->willReturn($response)
            ->shouldBeCalled();

        $this->assertSame(
            $response,
            $this->command->validate($this->request->reveal(), $this->acl->reveal())
        );
    }

    public function testValidationSucceedsForValidTweetUrls(): void
    {
        $userId = 'some-user-id';
        $url    = 'https://twitter.com/getlaminas/status/1239539812941651968';

        $this->request->userId()->willReturn($userId)->shouldBeCalled();
        $this->request->text()->willReturn($url)->shouldBeCalled();
        $this->acl->isAuthorized($userId)->willReturn(true)->shouldBeCalled();
        $this->responseFactory
            ->createUnauthorizedResponse()
            ->shouldNotBeCalled();
        $this->responseFactory
            ->createResponse(Argument::any())
            ->shouldNotBeCalled();

        $this->assertNull(
            $this->command->validate($this->request->reveal(), $this->acl->reveal())
        );
    }

    public function testDispatchDispatchesRetweetAndReturnsNull(): void
    {
        $url         = 'https://twitter.com/getlaminas/status/1239539812941651968';
        $responseUrl = 'http://localhost:9000/api/slack';

        $this->request->text()->willReturn($url)->shouldBeCalled();
        $this->request->responseUrl()->willReturn($responseUrl)->shouldBeCalled();

        // phpcs:disable SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.MissingVariable
        $this->dispatcher
            ->dispatch(Argument::that(function ($event) use ($url, $responseUrl) {
                TestCase::assertInstanceOf(Retweet::class, $event);
                /** @var Retweet $event */
                TestCase::assertSame($url, $event->original());
                TestCase::assertSame($responseUrl, $event->responseUrl());
                return $event;
            }))
            ->shouldBeCalled();
        // phpcs:enable SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.MissingVariable

        $this->responseFactory->createResponse(Argument::any())->shouldNotBeCalled();

        $this->assertNull($this->command->dispatch($this->request->reveal()));
    }
}
