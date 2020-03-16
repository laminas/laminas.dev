<?php

declare(strict_types=1);

namespace AppTest\Slack;

use App\Slack\HtmlToSlackFormatter;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function sprintf;

class HtmlToSlackFormatterTest extends TestCase
{
    public function htmlExamples(): iterable
    {
        yield 'links'          => ['links'];
        yield 'discourse-1439' => ['discourse-1439'];
        yield 'complex-html'   => ['complex-html'];
    }

    /** @dataProvider htmlExamples */
    public function testFormatsHtmlComprehensively(string $fixture): void
    {
        $html      = file_get_contents(sprintf('%s/../Fixtures/%s.html', __DIR__, $fixture));
        $expected  = file_get_contents(sprintf('%s/../Fixtures/%s-slackified.md', __DIR__, $fixture));
        $formatter = new HtmlToSlackFormatter();
        $this->assertSame($expected, $formatter->format($html));
    }
}
