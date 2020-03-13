<?php

declare(strict_types=1);

namespace AppTest\Slack;

use App\Slack\HtmlToSlackFormatter;
use PHPUnit\Framework\TestCase;

class HtmlToSlackFormatterTest extends TestCase
{
    public function testFormatsDiscourseLinks(): void
    {
        $string    = 'String with <a class="mention" href="/u/someuser">@Some_User</a> link';
        $formatter = new HtmlToSlackFormatter();

        $this->assertSame(
            'String with <https://discourse.laminas.dev/u/someuser|@Some_User> link',
            $formatter->format($string)
        );
    }
}
