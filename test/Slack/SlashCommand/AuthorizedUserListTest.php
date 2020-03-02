<?php

declare(strict_types=1);

namespace AppTest\Slack\SlashCommand;

use App\Slack\Response\SlackResponseInterface;
use App\Slack\SlackClientInterface;
use App\Slack\SlashCommand\AuthorizedUserList;
use DomainException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

class AuthorizedUserListTest extends TestCase
{
    /** @var AuthorizedUserList */
    private $list;

    /** @var RequestFactoryInterface|ObjectProphecy */
    private $requestFactory;

    /** @var SlackClientInterface|ObjectProphecy */
    private $slack;

    public function setUp(): void
    {
        $this->slack = $this->prophesize(SlackClientInterface::class);
        $this->requestFactory = $this->prophesize(RequestFactoryInterface::class);

        $this->list = new AuthorizedUserList(
            $this->slack->reveal(),
            $this->requestFactory->reveal()
        );
    }

    public function testNoUsersAreAuthorizedByDefault(): void
    {
        $this->assertFalse($this->list->isAuthorized('some-user-id'));
    }

    public function testBuildRaisesExceptionIfNonOkResponseReturned(): void
    {
        $error    = 'some error reason';
        $request  = $this->prophesize(RequestInterface::class);
        $request->withHeader('Accept', 'application/json; charset=utf-8')->will([$request, 'reveal'])->shouldBeCalled();

        $response = $this->prophesize(SlackResponseInterface::class);
        $response->isOk()->willReturn(false)->shouldBeCalled();
        $response->getError()->willReturn($error)->shouldBeCalled();
        $response->getPayload()->shouldNotBeCalled();

        $this->requestFactory
            ->createRequest(
                'GET',
                Argument::that(function (string $url) : string {
                    TestCase::assertStringContainsString('https://slack.com', $url);
                    TestCase::assertStringContainsString('/api/conversations.list?', $url);
                    TestCase::assertStringContainsString('exclude_archived=true', $url);
                    TestCase::assertStringContainsString('types=private_channel%2Cpublic_channel', $url);

                    return $url;
                })
            )
            ->will([$request, 'reveal'])
            ->shouldBeCalled();

        $this->slack->send($request)->will([$response, 'reveal'])->shouldBeCalled();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Unable to fetch list of channels: some error reason');
        $this->list->build();
    }

    public function testBuildRaisesExceptionIfTSCChannelNotFoundInChannelList(): void
    {
        $channelListPayload = [
            'channels' => [
                [
                    'id' => 'id-1',
                    'name' => 'not-tsc',
                ],
                [
                    'id' => 'id-2',
                    'name' => 'also-not-tsc',
                ],
            ],
        ];

        $channelNameList = [
            'not-tsc',
            'also-not-tsc',
        ];

        $request  = $this->prophesize(RequestInterface::class);
        $request->withHeader('Accept', 'application/json; charset=utf-8')->will([$request, 'reveal'])->shouldBeCalled();

        $response = $this->prophesize(SlackResponseInterface::class);
        $response->isOk()->willReturn(true)->shouldBeCalled();
        $response->getError()->shouldNotBeCalled();
        $response->getPayload()->willReturn($channelListPayload)->shouldBeCalled();

        $this->requestFactory
            ->createRequest(
                'GET',
                Argument::that(function (string $url) : string {
                    TestCase::assertStringContainsString('https://slack.com', $url);
                    TestCase::assertStringContainsString('/api/conversations.list?', $url);
                    TestCase::assertStringContainsString('exclude_archived=true', $url);
                    TestCase::assertStringContainsString('types=private_channel%2Cpublic_channel', $url);

                    return $url;
                })
            )
            ->will([$request, 'reveal'])
            ->shouldBeCalled();

        $this->slack->send($request)->will([$response, 'reveal'])->shouldBeCalled();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(sprintf(
            'Did not find #technical-steering-committee channel in list: %s',
            implode(', ', $channelNameList)
        ));
        $this->list->build();
    }

    public function testBuildRaisesExceptionIfRequestToFetchChannelMembersFails(): void
    {
        $tscChannelId = 'the-tsc-channel-id';

        $channelListPayload = [
            'channels' => [
                [
                    'id' => 'id-1',
                    'name' => 'not-tsc',
                ],
                [
                    'id' => $tscChannelId,
                    'name' => 'technical-steering-committee',
                ],
                [
                    'id' => 'id-2',
                    'name' => 'also-not-tsc',
                ],
            ],
        ];

        $listRequest  = $this->prophesize(RequestInterface::class);
        $listRequest
            ->withHeader('Accept', 'application/json; charset=utf-8')
            ->will([$listRequest, 'reveal'])
            ->shouldBeCalled();

        $memberRequest  = $this->prophesize(RequestInterface::class);
        $memberRequest
            ->withHeader('Accept', 'application/json; charset=utf-8')
            ->will([$memberRequest, 'reveal'])
            ->shouldBeCalled();

        $this->requestFactory
            ->createRequest(
                'GET',
                Argument::that(function (string $url) use ($tscChannelId) : string {
                    TestCase::assertStringContainsString('https://slack.com', $url);

                    if (strpos($url, '/api/conversations.list?') !== false) {
                        TestCase::assertStringContainsString('exclude_archived=true', $url);
                        TestCase::assertStringContainsString('types=private_channel%2Cpublic_channel', $url);
                    }

                    if (strpos($url, '/api/converations.members?') !== false) {
                        TestCase::assertStringContainsString('channel=' . $tscChannelId, $url);
                    }

                    return $url;
                })
            )
            ->will(function ($args) use ($listRequest, $memberRequest) {
                $url = $args[1];

                if (strpos($url, '/api/conversations.list?') !== false) {
                    return $listRequest->reveal();
                }

                if (strpos($url, '/api/conversations.members?') !== false) {
                    return $memberRequest->reveal();
                }

                throw new RuntimeException(sprintf(
                    'Cannot match URL to a request: %s (from %s)',
                    $url,
                    var_export($args, true)
                ));
            })
            ->shouldBeCalledTimes(2);

        $listResponse = $this->prophesize(SlackResponseInterface::class);
        $listResponse->isOk()->willReturn(true)->shouldBeCalled();
        $listResponse->getError()->shouldNotBeCalled();
        $listResponse->getPayload()->willReturn($channelListPayload)->shouldBeCalled();

        $memberResponse = $this->prophesize(SlackResponseInterface::class);
        $memberResponse->isOk()->willReturn(false)->shouldBeCalled();
        $memberResponse->getError()->willReturn('some error')->shouldBeCalled();
        $memberResponse->getPayload()->shouldNotBeCalled();

        $this->slack
            ->send(Argument::that(function (RequestInterface $request) use ($listRequest, $memberRequest) {
                TestCase::assertTrue(in_array($request, [$listRequest->reveal(), $memberRequest->reveal()], true));
                return $request;
            }))
            ->will(function ($args) use ($listRequest, $listResponse, $memberRequest, $memberResponse) {
                $request = $args[0];

                if ($request === $listRequest->reveal()) {
                    return $listResponse->reveal();
                }

                if ($request === $memberRequest->reveal()) {
                    return $memberResponse->reveal();
                }

                throw new RuntimeException('Cannot match request to a response');
            });

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Unable to fetch list of channel members: some error');
        $this->list->build();
    }

    public function testBuildMemoizesListOfAllowedUsersWhenAllApiCallsSucceed(): void
    {
        $tscChannelId = 'the-tsc-channel-id';
        $memberIds = [
            'member-id-1',
            'member-id-2',
            'member-id-3',
        ];

        foreach ($memberIds as $id) {
            $this->assertFalse($this->list->isAuthorized($id));
        }

        $channelListPayload = [
            'channels' => [
                [
                    'id' => 'id-1',
                    'name' => 'not-tsc',
                ],
                [
                    'id' => $tscChannelId,
                    'name' => 'technical-steering-committee',
                ],
                [
                    'id' => 'id-2',
                    'name' => 'also-not-tsc',
                ],
            ],
        ];

        $listRequest  = $this->prophesize(RequestInterface::class);
        $listRequest
            ->withHeader('Accept', 'application/json; charset=utf-8')
            ->will([$listRequest, 'reveal'])
            ->shouldBeCalled();

        $memberRequest  = $this->prophesize(RequestInterface::class);
        $memberRequest
            ->withHeader('Accept', 'application/json; charset=utf-8')
            ->will([$memberRequest, 'reveal'])
            ->shouldBeCalled();

        $this->requestFactory
            ->createRequest(
                'GET',
                Argument::that(function (string $url) use ($tscChannelId) : string {
                    TestCase::assertStringContainsString('https://slack.com', $url);

                    if (strpos($url, '/api/conversations.list?') !== false) {
                        TestCase::assertStringContainsString('exclude_archived=true', $url);
                        TestCase::assertStringContainsString('types=private_channel%2Cpublic_channel', $url);
                    }

                    if (strpos($url, '/api/converations.members?') !== false) {
                        TestCase::assertStringContainsString('channel=' . $tscChannelId, $url);
                    }

                    return $url;
                })
            )
            ->will(function ($args) use ($listRequest, $memberRequest) {
                $url = $args[1];

                if (strpos($url, '/api/conversations.list?') !== false) {
                    return $listRequest->reveal();
                }

                if (strpos($url, '/api/conversations.members?') !== false) {
                    return $memberRequest->reveal();
                }

                throw new RuntimeException(sprintf(
                    'Cannot match URL to a request: %s (from %s)',
                    $url,
                    var_export($args, true)
                ));
            })
            ->shouldBeCalledTimes(2);

        $listResponse = $this->prophesize(SlackResponseInterface::class);
        $listResponse->isOk()->willReturn(true)->shouldBeCalled();
        $listResponse->getError()->shouldNotBeCalled();
        $listResponse->getPayload()->willReturn($channelListPayload)->shouldBeCalled();

        $memberResponse = $this->prophesize(SlackResponseInterface::class);
        $memberResponse->isOk()->willReturn(true)->shouldBeCalled();
        $memberResponse->getError()->shouldNotBeCalled();
        $memberResponse->getPayload()->willReturn(['members' => $memberIds])->shouldBeCalled();

        $this->slack
            ->send(Argument::that(function (RequestInterface $request) use ($listRequest, $memberRequest) {
                TestCase::assertTrue(in_array($request, [$listRequest->reveal(), $memberRequest->reveal()], true));
                return $request;
            }))
            ->will(function ($args) use ($listRequest, $listResponse, $memberRequest, $memberResponse) {
                $request = $args[0];

                if ($request === $listRequest->reveal()) {
                    return $listResponse->reveal();
                }

                if ($request === $memberRequest->reveal()) {
                    return $memberResponse->reveal();
                }

                throw new RuntimeException('Cannot match request to a response');
            });

        $this->list->build();
        
        foreach ($memberIds as $id) {
            $this->assertTrue($this->list->isAuthorized($id));
        }
        $this->assertFalse($this->list->isAuthorized('some-invalid-id'));
    }
}
