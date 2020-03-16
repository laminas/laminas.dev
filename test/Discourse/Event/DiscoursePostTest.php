<?php

namespace AppTest\Discourse\Event;

use App\Discourse\Event\DiscoursePost;
use PHPUnit\Framework\TestCase;

use function time;

class DiscoursePostTest extends TestCase
{
    public function testEnsuresLinksPrefixTheBaseUrlToDiscourse(): void
    {
        $timestamp = time();
        $post      = new DiscoursePost(
            'qanda',
            [
                'post' => [
                    'hidden'      => false,
                    'created_at'  => $timestamp,
                    'updated_at'  => $timestamp,
                    'deleted_at'  => null,
                    'topic_slug'  => 'how-to-do-something',
                    'topic_id'    => 11111111,
                    'id'          => 5,
                    'topic_title' => 'How to do something',
                    'username'    => 'somebody',
                    'name'        => 'Some Body',
                    'cooked'      => '<p>This is html with a link to <a href="/u/some-id/">@A_User</a>',
                ],
            ],
            'http://localhost:9000'
        );

        $blocks       = $post->getMessageBlocks();
        $contentBlock = $blocks[1];
        $text         = $contentBlock['text']['text'];

        $this->assertRegExp('#\<http://localhost:9000/u/some-id/\|@A_User\>#', $text);
    }
}
