<?php

declare(strict_types=1);

namespace App\Discourse\Event;

use function array_key_exists;
use function ltrim;
use function sprintf;

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
        $this->channel      = sprintf('#%s', ltrim($channel, '#'));
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

        // Comment to allow broadcast of edit events
        if ($post['created_at'] !== $post['updated_at']) {
            return false;
        }

        return true;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getPostUrl(): string
    {
        $post = $this->payload['post'];
        return sprintf(
            '%s/t/%s/%s/%s',
            $this->discourseUrl,
            $post['topic_slug'],
            $post['topic_id'],
            $post['id'] ?? 1
        );
    }

    public function getFallbackMessage(): string
    {
        return sprintf(
            'Discourse: Comment created for %s: %s',
            $this->payload['post']['topic_title'],
            $this->getPostUrl()
        );
    }

    public function getMessageBlocks(): array
    {
        $post = $this->payload['post'];

        return [
            $this->createContextBlock(),
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => sprintf(
                        "<%s|*Comment created for %s by %s*>",
                        $this->getPostUrl(),
                        $post['topic_title'],
                        $post['name']
                    ),
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => $post['raw'],
                ],
            ],
            $this->createFieldsBlock($post),
        ];
    }

    private function createContextBlock(): array
    {
        return [
            'type'     => 'context',
            'elements' => [
                [
                    'type'      => 'image',
                    'image_url' => self::AUTHOR_ICON,
                    'alt_text'  => 'Discourse',
                ],
                [
                    'type' => 'mrkdwn',
                    'text' => sprintf('<%s|*Discourse*>', $this->discourseUrl),
                ],
            ],
        ];
    }

    private function createFieldsBlock(array $post): array
    {
        return [
            'type'   => 'section',
            'fields' => [
                [
                    'type' => 'mrkdwn',
                    'text' => '*In reply to*',
                ],
                [
                    'type' => 'mrkdwn',
                    'text' => '*Posted by*',
                ],
                [
                    'type' => 'mrkdwn',
                    'text' => sprintf(
                        '<%s/t/%s/%s|%s>',
                        $this->discourseUrl,
                        $post['topic_slug'],
                        $post['topic_id'],
                        $post['topic_title']
                    ),
                ],
                [
                    'type' => 'mrkdwn',
                    'text' => sprintf(
                        '<%s/u/%s|%s>',
                        $this->discourseUrl,
                        $post['username'],
                        $post['name']
                    ),
                ],
            ],
        ];
    }
}
