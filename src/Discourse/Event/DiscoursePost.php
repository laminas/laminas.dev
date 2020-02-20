<?php

declare(strict_types=1);

namespace App\Discourse\Event;

use DateTimeImmutable;

class DiscoursePost
{
    // phpcs:disable
    private const AUTHOR_ICON = 'https://slack-imgs.com/?c=1&o1=wi16.he16&url=https%3A%2F%2Fdiscourse-meta.s3-us-west-1.amazonaws.com%2Foriginal%2F3X%2Fc%2Fb%2Fcb4bec8901221d4a646e45e1fa03db3a65e17f59.png';

    private const COLOR = '#295473';

    private const FOOTER_ICON = 'https://slack-imgs.com/?c=1&o1=wi16.he16&url=https%3A%2F%2Fdiscourse-meta.s3-us-west-1.amazonaws.com%2Foriginal%2F3X%2Fc%2Fb%2Fcb4bec8901221d4a646e45e1fa03db3a65e17f59.png';
    // phpcs:enable

    /** @var string */
    private $channel;

    /** @var string */
    private $discourseUrl;

    /** @var array */
    private $payload;

    public function __construct(string $channel, array $payload, string $discourseUrl)
    {
        $this->channel      = $channel;
        $this->payload      = $payload;
        $this->discourseUrl = $discourseUrl;
    }

    public function isValidForSlack(): bool
    {
        if (! isset($this->payload['post'])) {
            return false;
        }

        $post = $this->payload['post'];

        if (array_key_exists('hidden', $post) && $post['hidden']) {
            return false;
        }

        if (array_key_exists('deleted_at', $post) && $post['deleted_at']) {
            return false;
        }

        // Uncomment to allow broadcast of edit events
        if ($post['created_at'] !== $post['updated_at']) {
            return false;
        }

        return true;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getMessagePayload(): array
    {
        $post      = $this->payload['post'];
        $timestamp = (new DateTimeImmutable($post['created_at']))->getTimestamp();
        $url       = sprintf(
            '%s/t/%s/%s/%s',
            $this->discourseUrl,
            $post['topic_slug'],
            $post['topic_id'],
            $post['id']
        );

        return [
            'color'       => self::COLOR,
            'fallback'    => sprintf('Discourse: Comment created for %s: %s', $post['topic_title'], $url),
            'author_name' => 'Discourse',
            'author_link' => $this->discourseUrl,
            'author_icon' => self::AUTHOR_ICON,
            'title'       => sprintf('Comment created for %s by %s', $post['topic_title'], $post['name']),
            'title_link'  => $url,
            'text'        => $post['cooked'],
            'fields'      => [
                [
                    'title' => 'In reply to',
                    'value' => sprintf(
                        '<%s/t/%s/%s|%s>',
                        $this->discourseUrl,
                        $post['topic_slug'],
                        $post['topic_id'],
                        $post['topic_title']
                    ),
                    'short'  => true,
                ],
                [
                    'title' => 'Posted by',
                    'value' => sprintf(
                        '<%s/u/%s|%s>',
                        $this->discourseUrl,
                        $post['username'],
                        $post['name']
                    ),
                    'short'  => true,
                ],
            ],
            'footer'      => 'Discourse',
            'footer_icon' => self::FOOTER_ICON,
            'ts'          => $timestamp,
        ];
    }
}
