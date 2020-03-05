<?php

declare(strict_types=1);

namespace AppTest\Slack\Domain;

use App\Slack\Domain\Block;
use App\Slack\Domain\ContextBlock;
use App\Slack\Domain\SectionBlock;
use App\Slack\Domain\TextObject;
use Assert\AssertionFailedException;
use Assert\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BlockTest extends TestCase
{
    public function testRaisesExceptionIfTypeKeyIsMissing(): void
    {
        $this->expectException(AssertionFailedException::class);
        Block::create([]);
    }

    public function testRaisesExceptionIfTypeKeyIsInvalid(): void
    {
        $this->expectException(AssertionFailedException::class);
        Block::create(['type' => 'unknown-type']);
    }

    public function testValidatesBlockBeforeReturningIt(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // No text or fields == invalid
        Block::create([
            'type'      => 'section',
            'accessory' => [
                'image_url' => 'https://getlaminas.org/images/logo/laminas-foundation-rgb.svg',
                'alt_text'  => 'the alt text',
            ],
        ]);
    }

    public function testCanReturnContextBlock(): void
    {
        $block = Block::create([
            'type'     => 'context',
            'elements' => [
                [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => 'Some text',
                ],
                [
                    'type' => TextObject::TYPE_PLAIN_TEXT,
                    'text' => 'Some text',
                ],
                [
                    'type'      => 'image',
                    'image_url' => 'https://getlaminas.org/images/logo/laminas-foundation-rgb.svg',
                    'alt_text'  => 'the alt text',
                ],
            ],
        ]);

        $this->assertInstanceOf(ContextBlock::class, $block);

        $this->assertSame([
            'type'     => 'context',
            'elements' => [
                [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => 'Some text',
                ],
                [
                    'type' => TextObject::TYPE_PLAIN_TEXT,
                    'text' => 'Some text',
                ],
                [
                    'type'      => 'image',
                    'image_url' => 'https://getlaminas.org/images/logo/laminas-foundation-rgb.svg',
                    'alt_text'  => 'the alt text',
                ],
            ],
        ], $block->toArray());
    }

    public function sectionBlockAssertions(): iterable
    {
        yield 'text-only' => [
            [
                'type' => 'section',
                'text' => [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => 'The section text',
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => 'The section text',
                ],
            ],
        ];

        yield 'fields-only' => [
            [
                'type'   => 'section',
                'fields' => [
                    [
                        'type' => TextObject::TYPE_MARKDOWN,
                        'text' => '*Label*',
                    ],
                    [
                        'type' => TextObject::TYPE_MARKDOWN,
                        'text' => 'Value',
                    ],
                ],
            ],
            [
                'type'   => 'section',
                'fields' => [
                    [
                        'type' => TextObject::TYPE_MARKDOWN,
                        'text' => '*Label*',
                    ],
                    [
                        'type' => TextObject::TYPE_MARKDOWN,
                        'text' => 'Value',
                    ],
                ],
            ],
        ];

        yield 'kitchen-sink' => [
            [
                'type'      => 'section',
                'text'      => [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => 'The section text',
                ],
                'fields'    => [
                    [
                        'type' => TextObject::TYPE_MARKDOWN,
                        'text' => '*Label*',
                    ],
                    [
                        'type' => TextObject::TYPE_MARKDOWN,
                        'text' => 'Value',
                    ],
                ],
                'accessory' => [
                    'image_url' => 'https://getlaminas.org/images/logo/laminas-foundation-rgb.svg',
                    'alt_text'  => 'the alt text',
                ],
            ],
            [
                'type'      => 'section',
                'text'      => [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => 'The section text',
                ],
                'fields'    => [
                    [
                        'type' => TextObject::TYPE_MARKDOWN,
                        'text' => '*Label*',
                    ],
                    [
                        'type' => TextObject::TYPE_MARKDOWN,
                        'text' => 'Value',
                    ],
                ],
                'accessory' => [
                    'type'      => 'image',
                    'image_url' => 'https://getlaminas.org/images/logo/laminas-foundation-rgb.svg',
                    'alt_text'  => 'the alt text',
                ],
            ],
        ];
    }

    /** @dataProvider sectionBlockAssertions */
    public function testCanReturnSectionBlock(array $definition, array $expected): void
    {
        $block = Block::create($definition);

        $this->assertInstanceOf(SectionBlock::class, $block);
        $this->assertSame($expected, $block->toArray());
    }
}
