<?php

declare(strict_types=1);

namespace App\Slack\Domain;

use Assert\Assert;
use Assert\AssertionFailedException;

class Block
{
    /**
     * @throws AssertionFailedException for invalid block types or malformed
     *     blocks
     */
    public static function create(array $payload): BlockInterface
    {
        Assert::that($payload)->keyIsset('type');
        Assert::that($payload['type'])->string()->inArray(['section', 'context']);
        switch ($payload['type']) {
            case 'context':
                $block = ContextBlock::fromArray($payload);
                break;
            case 'section':
            default:
                $block = SectionBlock::fromArray($payload);
                break;
        }
        $block->validate();
        return $block;
    }
}
