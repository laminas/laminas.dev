<?php

declare(strict_types=1);

namespace AppTest\GitHub\Event;

use App\GitHub\Event\GitHubPullRequest;
use PHPUnit\Framework\TestCase;

use function end;
use function reset;
use function sprintf;

class GitHubPullRequestTest extends TestCase
{
    public function testMessageBlocksContainsCorrectContextBlockAdditionalLine(): void
    {
        $payload = $this->createValidPayload();
        $event   = new GitHubPullRequest($payload);

        $messageBlocks = $event->getMessageBlocks();

        self::assertCount(3, $messageBlocks);
        $contextBlock = reset($messageBlocks);
        self::assertIsArray($contextBlock);
        self::assertSame('context', $contextBlock['type']);
        self::assertIsArray($contextBlock['elements']);
        self::assertCount(3, $contextBlock['elements']);
        $additionalText = end($contextBlock['elements']);
        $expectedText   = sprintf(
            '<%s|*[%s] Pull request %s#%s: %s*>',
            $payload['pull_request']['html_url'],
            $payload['action'],
            $payload['repository']['full_name'],
            $payload['pull_request']['number'],
            $payload['pull_request']['title']
        );
        self::assertSame($expectedText, $additionalText['text']);
    }

    /**
     * @psalm-return array<string, string|array<string, string|int>>
     */
    private function createValidPayload(): array
    {
        return [
            'action'       => 'opened',
            'repository'   => [
                'full_name' => 'laminas/laminas.dev',
                'html_url'  => 'https://github.com/laminas/laminas.dev',
            ],
            'pull_request' => [
                'html_url' => 'https://github.com/laminas/laminas.dev/pull/5',
                'number'   => 5,
                'title'    => 'fix a missing sprintf placeholder in pull request title',
                'body'     => 'This is the PR body',
            ],
            'sender'       => [
                'login'    => 'Laminas',
                'html_url' => 'https://github.com/laminas',
            ],
        ];
    }
}
