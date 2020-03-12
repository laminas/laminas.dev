<?php

declare(strict_types=1);

namespace AppTest\GitHub\Listener;

use App\GitHub\Event\GitHubStatus;
use App\GitHub\GitHubClient;
use App\GitHub\Listener\GitHubStatusListener;
use App\Slack\Domain\ContextBlock;
use App\Slack\Domain\SectionBlock;
use App\Slack\Domain\TextObject;
use App\Slack\Domain\WebAPIMessage;
use App\Slack\Response\SlackResponseInterface;
use App\Slack\SlackClientInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

use function file_get_contents;
use function json_decode;
use function json_encode;
use function substr;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class GitHubStatusListenerTest extends TestCase
{
    /** @var string */
    private $channel;

    /** @var GitHubClient|ObjectProphecy */
    private $githubClient;

    /** @var GitHubStatusListener */
    private $listener;

    /** @var LoggerInterface|ObjectProphecy */
    private $logger;

    /** @var SlackClientInterface|ObjectProphecy */
    private $slack;

    public function setUp(): void
    {
        $this->channel      = 'github';
        $this->slack        = $this->prophesize(SlackClientInterface::class);
        $this->githubClient = $this->prophesize(GitHubClient::class);
        $this->logger       = $this->prophesize(LoggerInterface::class);

        $this->listener = new GitHubStatusListener(
            $this->channel,
            $this->slack->reveal(),
            $this->githubClient->reveal(),
            $this->logger->reveal()
        );
    }

    public function testNotifiesSlackWithGenericMessageIfStatusIsNotForAPullRequest(): void
    {
        $json    = file_get_contents(__DIR__ . '/../../Fixtures/status-success.json');
        $payload = json_decode($json, true);
        $status  = new GitHubStatus($payload);

        $response = $this->prophesize(SlackResponseInterface::class)->reveal();
        $this->slack
            ->sendWebAPIMessage(Argument::that(function ($message) use ($payload) {
                TestCase::assertInstanceOf(WebAPIMessage::class, $message);

                TestCase::assertSame('#github', $message->getChannel());
                TestCase::assertStringContainsString($payload['repository']['full_name'], $message->getText());
                TestCase::assertStringContainsString(substr($payload['sha'], 0, 8), $message->getText());
                TestCase::assertStringContainsString($payload['target_url'], $message->getText());

                $blocks = $message->getBlocks();

                TestCase::assertCount(2, $blocks);

                $context = $blocks[0];
                TestCase::assertInstanceOf(ContextBlock::class, $context);
                $summary = $context->getElements()[2];
                TestCase::assertInstanceOf(TextObject::class, $summary);
                TestCase::assertSame(TextObject::TYPE_MARKDOWN, $summary->toArray()['type']);
                TestCase::assertStringContainsString($payload['target_url'], $summary->toArray()['text']);

                $fields = $blocks[1];
                TestCase::assertInstanceOf(SectionBlock::class, $fields);
                TestCase::assertCount(3, $fields->getFields());

                return $message;
            }))
            ->willReturn($response)
            ->shouldBeCalled();

        $this->githubClient->createRequest(Argument::any())->shouldNotBeCalled();
        $this->githubClient->send(Argument::any())->shouldNotBeCalled();

        $this->assertNull($this->listener->__invoke($status));
    }

    public function testLogsErrorAndNotifiesSlackWithGenericMessageForPullRequestWhereSearchFails(): void
    {
        $json    = file_get_contents(__DIR__ . '/../../Fixtures/status-success-for-pr.json');
        $payload = json_decode($json, true);
        $status  = new GitHubStatus($payload);

        $response = $this->prophesize(SlackResponseInterface::class)->reveal();
        $this->slack
            ->sendWebAPIMessage(Argument::that(function ($message) use ($payload) {
                TestCase::assertInstanceOf(WebAPIMessage::class, $message);

                TestCase::assertSame('#github', $message->getChannel());
                TestCase::assertStringContainsString($payload['repository']['full_name'], $message->getText());
                TestCase::assertStringContainsString(substr($payload['sha'], 0, 8), $message->getText());
                TestCase::assertStringContainsString($payload['target_url'], $message->getText());

                $blocks = $message->getBlocks();

                TestCase::assertCount(2, $blocks);

                $context = $blocks[0];
                TestCase::assertInstanceOf(ContextBlock::class, $context);
                TestCase::assertCount(3, $context->getElements());
                $summary = $context->getElements()[2];
                TestCase::assertInstanceOf(TextObject::class, $summary);
                TestCase::assertSame(TextObject::TYPE_MARKDOWN, $summary->toArray()['type']);
                TestCase::assertStringContainsString($payload['target_url'], $summary->toArray()['text']);

                $fields = $blocks[1];
                TestCase::assertInstanceOf(SectionBlock::class, $fields);
                TestCase::assertCount(3, $fields->getFields());

                return $message;
            }))
            ->willReturn($response)
            ->shouldBeCalled();

        $ghRequest  = $this->prophesize(RequestInterface::class);
        $ghResponse = $this->prophesize(ResponseInterface::class);
        $ghResponse->getStatusCode()->willReturn(400)->shouldBeCalled();
        $ghResponse->getBody()->willReturn('')->shouldBeCalled();

        $this->githubClient
            ->createRequest(
                'GET',
                Argument::that(function ($url) {
                    TestCase::assertInternalType('string', $url);
                    TestCase::assertStringContainsString('?repo:zendframework/zend-diactoros', $url);

                    return $url;
                })
            )
            ->will([$ghRequest, 'reveal'])
            ->shouldBeCalled();

        $this->githubClient
            ->send(Argument::that([$ghRequest, 'reveal']))
            ->will([$ghResponse, 'reveal'])
            ->shouldBeCalled();

        $this->logger
            ->error(Argument::containingString('for zendframework/zend-diactoros@gh-pages (' . $payload['sha'] . ')'))
            ->shouldBeCalled();

        $this->assertNull($this->listener->__invoke($status));
    }

    public function inconclusiveIssueSearchResults(): iterable
    {
        yield 'empty-response'      => [''];
        yield 'malformed-response'  => ['[ /* this is malformed */'];
        yield 'incomplete-response' => ['{"incomplete_results":true}'];
        yield 'no-items-returned'   => ['{"incomplete_results":false,"items":[]}'];
    }

    /** @dataProvider inconclusiveIssueSearchResults */
    public function testNotifiesSlackWithGenericMessageForPullRequestWhereSearchResultsInconclusive(
        string $payload
    ): void {
        $json    = file_get_contents(__DIR__ . '/../../Fixtures/status-success-for-pr.json');
        $payload = json_decode($json, true);
        $status  = new GitHubStatus($payload);

        $response = $this->prophesize(SlackResponseInterface::class)->reveal();
        $this->slack
            ->sendWebAPIMessage(Argument::that(function ($message) use ($payload) {
                TestCase::assertInstanceOf(WebAPIMessage::class, $message);

                TestCase::assertSame('#github', $message->getChannel());
                TestCase::assertStringContainsString($payload['repository']['full_name'], $message->getText());
                TestCase::assertStringContainsString(substr($payload['sha'], 0, 8), $message->getText());
                TestCase::assertStringContainsString($payload['target_url'], $message->getText());

                $blocks = $message->getBlocks();

                TestCase::assertCount(2, $blocks);

                $context = $blocks[0];
                TestCase::assertInstanceOf(ContextBlock::class, $context);
                TestCase::assertCount(3, $context->getElements());
                $summary = $context->getElements()[2];
                TestCase::assertInstanceOf(TextObject::class, $summary);
                TestCase::assertSame(TextObject::TYPE_MARKDOWN, $summary->toArray()['type']);
                TestCase::assertStringContainsString($payload['target_url'], $summary->toArray()['text']);

                $fields = $blocks[1];
                TestCase::assertInstanceOf(SectionBlock::class, $fields);
                TestCase::assertCount(3, $fields->getFields());

                return $message;
            }))
            ->willReturn($response)
            ->shouldBeCalled();

        $ghRequest  = $this->prophesize(RequestInterface::class);
        $ghResponse = $this->prophesize(ResponseInterface::class);
        $ghResponse->getStatusCode()->willReturn(200)->shouldBeCalled();
        $ghResponse->getBody()->willReturn($payload)->shouldBeCalled();

        $this->githubClient
            ->createRequest(
                'GET',
                Argument::that(function ($url) {
                    TestCase::assertInternalType('string', $url);
                    TestCase::assertStringContainsString('?repo:zendframework/zend-diactoros', $url);

                    return $url;
                })
            )
            ->will([$ghRequest, 'reveal'])
            ->shouldBeCalled();

        $this->githubClient
            ->send(Argument::that([$ghRequest, 'reveal']))
            ->will([$ghResponse, 'reveal'])
            ->shouldBeCalled();

        $this->logger->error(Argument::any())->shouldNotBeCalled();

        $this->assertNull($this->listener->__invoke($status));
    }

    public function testNotifesSlackWithPullRequestBuildStatusWhenSearchHasAtLeastOneMatchingResult(): void
    {
        $json    = file_get_contents(__DIR__ . '/../../Fixtures/status-success-for-pr.json');
        $payload = json_decode($json, true);
        $status  = new GitHubStatus($payload);

        $response = $this->prophesize(SlackResponseInterface::class)->reveal();
        $this->slack
            ->sendWebAPIMessage(Argument::that(function ($message) use ($payload) {
                TestCase::assertInstanceOf(WebAPIMessage::class, $message);

                TestCase::assertSame('#github', $message->getChannel());
                TestCase::assertStringContainsString($payload['repository']['full_name'], $message->getText());
                TestCase::assertStringContainsString('for pull request', $message->getText());
                TestCase::assertStringContainsString('Pull request title', $message->getText());
                TestCase::assertStringContainsString('pull-request-url', $message->getText());

                $blocks = $message->getBlocks();

                TestCase::assertCount(2, $blocks);

                $context = $blocks[0];
                TestCase::assertInstanceOf(ContextBlock::class, $context);
                TestCase::assertCount(3, $context->getElements());
                $summary = $context->getElements()[2];
                TestCase::assertInstanceOf(TextObject::class, $summary);
                TestCase::assertSame(TextObject::TYPE_MARKDOWN, $summary->toArray()['type']);
                TestCase::assertStringContainsString('for pull request', $summary->toArray()['text']);

                $fields = $blocks[1];
                TestCase::assertInstanceOf(SectionBlock::class, $fields);
                TestCase::assertCount(3, $fields->getFields());
                TestCase::assertStringContainsString('*Pull Request*', $fields->getFields()[2]->toArray()['text']);

                return $message;
            }))
            ->willReturn($response)
            ->shouldBeCalled();

        $ghRequest = $this->prophesize(RequestInterface::class);

        $ghResponsePayload = json_encode([
            'incomplete_results' => false,
            'items'              => [
                [
                    'number'   => 1234,
                    'title'    => 'Pull request title',
                    'html_url' => 'pull-request-url',
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $ghResponse = $this->prophesize(ResponseInterface::class);
        $ghResponse->getStatusCode()->willReturn(200)->shouldBeCalled();
        $ghResponse->getBody()->willReturn($ghResponsePayload)->shouldBeCalled();

        $this->githubClient
            ->createRequest(
                'GET',
                Argument::that(function ($url) {
                    TestCase::assertInternalType('string', $url);
                    TestCase::assertStringContainsString('?repo:zendframework/zend-diactoros', $url);

                    return $url;
                })
            )
            ->will([$ghRequest, 'reveal'])
            ->shouldBeCalled();

        $this->githubClient
            ->send(Argument::that([$ghRequest, 'reveal']))
            ->will([$ghResponse, 'reveal'])
            ->shouldBeCalled();

        $this->logger->error(Argument::any())->shouldNotBeCalled();

        $this->assertNull($this->listener->__invoke($status));
    }
}
