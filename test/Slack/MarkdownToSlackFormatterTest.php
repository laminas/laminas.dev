<?php

declare(strict_types=1);

namespace AppTest\Slack;

use App\Slack\MarkdownToSlackFormatter;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function sprintf;

class MarkdownToSlackFormatterTest extends TestCase
{
    public function markdownExamples(): iterable
    {
        yield 'issue'   => ['markdown-issue'];
        yield 'complex' => ['markdown-complex'];
    }

    /** @dataProvider markdownExamples */
    public function testFormatting(string $fixture): void
    {
        $markdown = file_get_contents(sprintf('%s/../Fixtures/%s.md', __DIR__, $fixture));
        $expected = file_get_contents(sprintf('%s/../Fixtures/%s-slackified.md', __DIR__, $fixture));

        $formatter = new MarkdownToSlackFormatter();

        $this->assertSame($expected, $formatter->format($markdown));
    }
}
