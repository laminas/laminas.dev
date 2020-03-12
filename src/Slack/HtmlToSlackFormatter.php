<?php

declare(strict_types=1);

namespace App\Slack;

/**
 * @see https://gist.github.com/SiamKreative/0098993097bdf5cea5da#gistcomment-2241255
 */
class HtmlToSlackFormatter
{
    public function format(string $html): string
    {
        $markdown = strip_tags($html, '<br><strong><em><del><li><code><pre><a>');
        $markdown = str_replace(['<br />', '<br>'], "\n", $markdown);
        $markdown = str_replace(['<strong>', '</strong>'], '*', $markdown);
        $markdown = str_replace(['<em>', '</em>'], '_', $markdown);
        $markdown = str_replace(['<del>', '</del>'], '~', $markdown);
        $markdown = str_replace(['<li>', '</li>'], ['•', ''], $markdown);
        $markdown = str_replace(['<code>', '</code>'], '`', $markdown);
        $markdown = str_replace(['<pre>', '</pre>'], '```', $markdown);

        preg_match_all('/<a.*?href=\"(.*?)\".*?>(.*?)<\/a>/i', $markdown, $res);
        $results = count($res[0]);
        for ($i = 0; $i < $results; $i++) {
            $url = $res[1][$i];
            if (! preg_match('#^https?://.*?/#', $url)) {
                $url = sprintf('https://discourse.laminas.dev/%s', ltrim($url, '/'));
            }

            $markdown = str_replace(
                $res[0][$i],
                sprintf('<%s|%s>', $url, $res[2][$i]),
                $markdown
            );
        }

        return $markdown;
    }
}
