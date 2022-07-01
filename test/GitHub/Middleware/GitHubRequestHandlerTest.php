<?php

declare(strict_types=1);

namespace AppTest\GitHub\Middleware;

use App\GitHub\Event\GitHubIssue;
use App\GitHub\Event\GitHubPullRequest;
use App\GitHub\Event\GitHubRelease;
use App\GitHub\Event\GitHubStatus;
use App\GitHub\Middleware\GitHubRequestHandler;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function file_get_contents;
use function json_decode;

class GitHubRequestHandlerTest extends TestCase
{
    /** @var EventDispatcherInterface|ObjectProphecy */
    private $dispatcher;

    /** @var ServerRequestInterface|ObjectProphecy */
    private $request;

    protected function setUp(): void
    {
        $this->dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->request    = $this->prophesize(ServerRequestInterface::class);
    }

    public function testPingRequest(): void
    {
        $json    = file_get_contents(__DIR__ . '/../../Fixtures/ping.json');
        $payload = json_decode($json, true);

        $this->request->getHeaderLine('X-GitHub-Event')->willReturn('ping');
        $this->request->getParsedBody()->willReturn($payload);
        $this->dispatcher->dispatch(Argument::any())->shouldNotBeCalled();

        $handler  = new GitHubRequestHandler($this->dispatcher->reveal());
        $response = $handler->handle($this->request->reveal());

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertEquals(204, $response->getStatusCode());
        self::assertContains('Hello from ', (string) $response->getBody());
    }

    /** @dataProvider unhandledRequestProvider */
    public function testUnhandledRequest(string $event): void
    {
        $this->request->getHeaderLine('X-GitHub-Event')->willReturn($event);
        $this->request->getParsedBody()->willReturn(['foo' => 'bar']);
        $this->dispatcher->dispatch(Argument::any())->shouldNotBeCalled();

        $handler  = new GitHubRequestHandler($this->dispatcher->reveal());
        $response = $handler->handle($this->request->reveal());

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertEquals(204, $response->getStatusCode());
        self::assertContains('Received but not processed.', (string) $response->getBody());
    }

    public function unhandledRequestProvider(): array
    {
        return [
            'check_run'                      => ['check_run'],
            'check_suite'                    => ['check_suite'],
            'commit_comment'                 => ['commit_comment'],
            'content_reference'              => ['content_reference'],
            'create'                         => ['create'],
            'delete'                         => ['delete'],
            'deployment'                     => ['deployment'],
            'deployment_status'              => ['deployment_status'],
            'deploy_key'                     => ['deploy_key'],
            'fork'                           => ['fork'],
            'github_app_authorization'       => ['github_app_authorization'],
            'gollum'                         => ['gollum'],
            'installation'                   => ['installation'],
            'issues-closed'                  => ['issues-closed'],
            'issue-comment-created'          => ['comment-created'],
            'label'                          => ['label'],
            'marketplace_purchase'           => ['marketplace_purchase'],
            'member'                         => ['member'],
            'membership'                     => ['membership'],
            'meta'                           => ['meta'],
            'milestone'                      => ['milestone'],
            'organization'                   => ['organization'],
            'org_block'                      => ['org_block'],
            'page_build'                     => ['page_build'],
            'project'                        => ['project'],
            'project_card'                   => ['project_card'],
            'project_column'                 => ['project_column'],
            'public'                         => ['public'],
            'pull_request-closed'            => ['pull-request-closed'],
            'pull_request-merged'            => ['pull-request-merged'],
            'pull_request_review'            => ['pull_request_review'],
            'pull_request_review_comment'    => ['pull_request_review_comment'],
            'push'                           => ['push'],
            'package'                        => ['package'],
            'repository'                     => ['repository'],
            'repository_import'              => ['repository_import'],
            'repository_vulnerability_alert' => ['repository_vulnerability_alert'],
            'security_advisory'              => ['security_advisory'],
            'sponsorship_event'              => ['sponsorship_event'],
            'star'                           => ['star'],
            'status-success'                 => ['status-success'],
            'team'                           => ['team'],
            'team_add'                       => ['team_add'],
            'watch'                          => ['watch'],
            'foo'                            => ['foo'],
        ];
    }

    /** @dataProvider handledRequestProvider */
    public function testHandledRequest(string $fixture, string $event, string $type): void
    {
        $json    = file_get_contents(__DIR__ . '/../../Fixtures/' . $fixture);
        $payload = json_decode($json, true);

        $this->request->getHeaderLine('X-GitHub-Event')->willReturn($event);
        $this->request->getParsedBody()->willReturn($payload);
        $this->dispatcher->dispatch(Argument::type($type))->shouldBeCalled();

        $handler  = new GitHubRequestHandler($this->dispatcher->reveal());
        $response = $handler->handle($this->request->reveal());

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertInstanceOf(EmptyResponse::class, $response, (string) $response->getBody());
        self::assertEquals(204, $response->getStatusCode());
        self::assertEmpty((string) $response->getBody());
    }

    public function handledRequestProvider(): array
    {
        return [
            'issues-opened'         => ['issues-opened.json', 'issues', GitHubIssue::class],
            'issues-reopened'       => ['issues-reopened.json', 'issues', GitHubIssue::class],
            'pull_request-opened'   => ['pull-request-opened.json', 'pull_request', GitHubPullRequest::class],
            'pull_request-reopened' => ['pull-request-reopened.json', 'pull_request', GitHubPullRequest::class],
            'release-published'     => ['release-published.json', 'release', GitHubRelease::class],
            'status-error'          => ['status-error.json', 'status', GitHubStatus::class],
            'status-failure'        => ['status-failure.json', 'status', GitHubStatus::class],
        ];
    }

    /** @dataProvider ignoredRequestProvider */
    public function testIgnoredRequest(string $fixture, string $event): void
    {
        $json    = file_get_contents(__DIR__ . '/../../Fixtures/' . $fixture);
        $payload = json_decode($json, true);

        $this->request->getHeaderLine('X-GitHub-Event')->willReturn($event);
        $this->request->getParsedBody()->willReturn($payload);
        $this->dispatcher->dispatch(Argument::any())->shouldNotBeCalled();

        $handler  = new GitHubRequestHandler($this->dispatcher->reveal());
        $response = $handler->handle($this->request->reveal());

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertEquals(204, $response->getStatusCode());
        self::assertContains('Received but not processed.', (string) $response->getBody());
    }

    public function ignoredRequestProvider(): array
    {
        return [
            'issue-comment-deleted'   => ['comment-deleted.json', 'issue_comment'],
            'issue-comment-edited'    => ['comment-edited.json', 'issue_comment'],
            'issues-assigned'         => ['issues-assigned.json', 'issues'],
            'issues-deleted'          => ['issues-deleted.json', 'issues'],
            'issues-demilestoned'     => ['issues-demilestoned.json', 'issues'],
            'issues-edited'           => ['issues-edited.json', 'issues'],
            'issues-labeled'          => ['issues-labeled.json', 'issues'],
            'issues-locked'           => ['issues-locked.json', 'issues'],
            'issues-milestoned'       => ['issues-milestoned.json', 'issues'],
            'issues-pinned'           => ['issues-pinned.json', 'issues'],
            'issues-transferred'      => ['issues-transferred.json', 'issues'],
            'issues-unassigned'       => ['issues-unassigned.json', 'issues'],
            'issues-unlabeled'        => ['issues-unlabeled.json', 'issues'],
            'issues-unlocked'         => ['issues-unlocked.json', 'issues'],
            'issues-unpinned'         => ['issues-unpinned.json', 'issues'],
            'pull-request-assigned'   => ['pull-request-assigned.json', 'pull_request'],
            'pull-request-edited'     => ['pull-request-edited.json', 'pull_request'],
            'pull-request-labeled'    => ['pull-request-labeled.json', 'pull_request'],
            'pull-request-locked'     => ['pull-request-locked.json', 'pull_request'],
            'pull-request-ready'      => ['pull-request-ready.json', 'pull_request'],
            'pull-request-sync'       => ['pull-request-synchronize.json', 'pull_request'],
            'pull-request-unassigned' => ['pull-request-unassigned.json', 'pull_request'],
            'pull-request-unlabeled'  => ['pull-request-unlabeled.json', 'pull_request'],
            'release-created'         => ['release-created.json', 'release'],
            'release-deleted'         => ['release-deleted.json', 'release'],
            'release-edited'          => ['release-edited.json', 'release'],
            'release-prereleased'     => ['release-prereleased.json', 'release'],
            'release-unpublished'     => ['release-unpublished.json', 'release'],
            'status-pending'          => ['status-pending.json', 'status'],
        ];
    }

    public function testRespondsForMessageValidationErrors(): void
    {
        $json    = file_get_contents(__DIR__ . '/../../Fixtures/issues-invalid.json');
        $payload = json_decode($json, true);

        $this->request->getHeaderLine('X-GitHub-Event')->willReturn('issues');
        $this->request->getParsedBody()->willReturn($payload);
        $this->dispatcher->dispatch(Argument::any())->shouldNotBeCalled();

        $handler  = new GitHubRequestHandler($this->dispatcher->reveal());
        $response = $handler->handle($this->request->reveal());

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertEquals(400, $response->getStatusCode());

        $header = $response->getHeaderLine('X-Status-Reason');
        self::assertContains('Validation failed', $header);
    }
}
