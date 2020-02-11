<?php

declare(strict_types=1);

namespace AppTest\GitHub\Middleware;

use App\GitHub\Message\GitHubPullRequest;
use App\GitHub\Message\GitHubPush;
use App\GitHub\Message\GitHubStatus;
use App\GitHub\Middleware\GithubRequestHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use function file_get_contents;
use function json_decode;

class GithubRequestHandlerTest extends TestCase
{
    /** @var MessageBusInterface|ObjectProphecy */
    private $bus;

    /** @var ServerRequestInterface|ObjectProphecy */
    private $request;

    protected function setUp() : void
    {
        $this->bus     = $this->prophesize(MessageBusInterface::class);
        $this->request = $this->prophesize(ServerRequestInterface::class);
    }

    public function testPingRequest() : void
    {
        $json    = file_get_contents(__DIR__ . '/../../Fixtures/ping.json');
        $payload = json_decode($json, true);

        $this->request->getHeaderLine('X-GitHub-Event')->willReturn('ping');
        $this->request->getParsedBody()->willReturn($payload);
        $this->bus->dispatch(Argument::any())->shouldNotBeCalled();

        $handler  = new GithubRequestHandler($this->bus->reveal());
        $response = $handler->handle($this->request->reveal());

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertEquals(204, $response->getStatusCode());
        self::assertContains('Hello from ', (string) $response->getBody());
    }

    /** @dataProvider unhandledRequestProvider */
    public function testUnhandledRequest(string $event) : void
    {
        $this->request->getHeaderLine('X-GitHub-Event')->willReturn($event);
        $this->request->getParsedBody()->willReturn(['foo' => 'bar']);
        $this->bus->dispatch(Argument::any())->shouldNotBeCalled();

        $handler  = new GithubRequestHandler($this->bus->reveal());
        $response = $handler->handle($this->request->reveal());

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertEquals(204, $response->getStatusCode());
        self::assertContains('Received but not processed.', (string) $response->getBody());
    }

    public function unhandledRequestProvider() : array
    {
        return [
            ['deployment'],
            ['deployment_status'],
            ['default'],
            ['foo'],
        ];
    }

    /** @dataProvider handledRequestProvider */
    public function testHandledRequest(string $fixture, string $event, string $type) : void
    {
        $json    = file_get_contents(__DIR__ . '/../../Fixtures/' . $fixture);
        $payload = json_decode($json, true);

        $this->request->getHeaderLine('X-GitHub-Event')->willReturn($event);
        $this->request->getParsedBody()->willReturn($payload);
        $this->bus->dispatch(Argument::type($type))->shouldBeCalled();

        $handler  = new GithubRequestHandler($this->bus->reveal());
        $response = $handler->handle($this->request->reveal());

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertInstanceOf(EmptyResponse::class, $response);
        self::assertEquals(204, $response->getStatusCode());
        self::assertEmpty((string) $response->getBody());
    }

    public function handledRequestProvider() : array
    {
        return [
            ['commit-status-error.json', 'status', GitHubStatus::class],
            ['commit-status-failure.json', 'status', GitHubStatus::class],
            ['commit-status-success.json', 'status', GitHubStatus::class],
            ['pull-request-closed.json', 'pull_request', GitHubPullRequest::class],
            ['pull-request-merged.json', 'pull_request', GitHubPullRequest::class],
            ['pull-request-opened.json', 'pull_request', GitHubPullRequest::class],
            ['pull-request-reopened.json', 'pull_request', GitHubPullRequest::class],
            ['push-multiple.json', 'push', GitHubPush::class],
            ['push-single.json', 'push', GitHubPush::class],
        ];
    }

    /** @dataProvider ignoredRequestProvider */
    public function testIgnoredRequest(string $fixture, string $event, string $type) : void
    {
        $json    = file_get_contents(__DIR__ . '/../../Fixtures/' . $fixture);
        $payload = json_decode($json, true);

        $this->request->getHeaderLine('X-GitHub-Event')->willReturn($event);
        $this->request->getParsedBody()->willReturn($payload);
        $this->bus->dispatch(Argument::any())->shouldNotBeCalled();

        $handler  = new GithubRequestHandler($this->bus->reveal());
        $response = $handler->handle($this->request->reveal());

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertEquals(204, $response->getStatusCode());
        self::assertContains('Received but not processed.', (string) $response->getBody());
    }

    public function ignoredRequestProvider() : array
    {
        return [
            ['commit-status-pending.json', 'status', GitHubStatus::class],
            ['pull-request-synchronize.json', 'pull_request', GitHubPullRequest::class],
        ];
    }
}
