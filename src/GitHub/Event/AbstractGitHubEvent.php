<?php

declare(strict_types=1);

namespace App\GitHub\Event;

use App\Slack\Domain\TextObject;

use function sprintf;

abstract class AbstractGitHubEvent implements GitHubMessageInterface
{
    abstract public function getFallbackMessage(): string;

    abstract public function getMessageBlocks(): array;

    protected function createContextBlock(string $url): array
    {
        return [
            'type'     => 'context',
            'elements' => [
                [
                    'type'      => 'image',
                    'image_url' => self::GITHUB_ICON,
                    'alt_text'  => 'GitHub',
                ],
                [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => sprintf(
                        '<%s|*GitHub*>',
                        $url
                    ),
                ],
            ],
        ];
    }
}
