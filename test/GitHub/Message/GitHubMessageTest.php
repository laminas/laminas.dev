<?php

declare(strict_types=1);

namespace AppTest\GitHub\Message;

use App\GitHub\Message\GitHubMessageInterface;
use App\GitHub\Message\GitHubPullRequest;
use App\GitHub\Message\GitHubPush;
use App\GitHub\Message\GitHubStatus;
use PHPUnit\Framework\TestCase;
use function file_get_contents;
use function json_decode;

class GitHubMessageTest extends TestCase
{
    /**
     * @dataProvider messageProvider
     */
    public function testMessages(string $fixture, string $messageClass) : void
    {
        $json    = file_get_contents(__DIR__ . '/../../Fixtures/' . $fixture);
        $payload = json_decode($json, true);

        /** @var GitHubMessageInterface $message */
        $message = new $messageClass($payload);
        $message->validate();

        self::assertInstanceOf(GitHubMessageInterface::class, $message);
        self::assertInstanceOf($messageClass, $message);
    }

    public function messageProvider() : array
    {
        return [
            ['commit-status-error.json', GitHubStatus::class],
            ['commit-status-failure.json', GitHubStatus::class],
            ['commit-status-pending.json', GitHubStatus::class],
            ['commit-status-success.json', GitHubStatus::class],
            ['pull-request-closed.json', GitHubPullRequest::class],
            ['pull-request-merged.json', GitHubPullRequest::class],
            ['pull-request-opened.json', GitHubPullRequest::class],
            ['pull-request-reopened.json', GitHubPullRequest::class],
            ['pull-request-synchronize.json', GitHubPullRequest::class],
            ['push-multiple.json', GitHubPush::class],
            ['push-single.json', GitHubPush::class],
        ];
    }
}
