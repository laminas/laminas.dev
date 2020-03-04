<?php

declare(strict_types=1);

namespace AppTest\Slack\Domain;

use App\Slack\Domain\ImageElement;
use App\Slack\Domain\SectionBlock;
use App\Slack\Domain\TextObject;
use Assert\AssertionFailedException;
use Assert\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SectionBlockTest extends TestCase
{
    public function testMarksBlockInvalidIfNeitherTextNorFieldsArePresent(): void
    {
        $block = new SectionBlock();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('requires one or both of the "text" and "fields" keys');
        $block->validate();
    }

    public function testMarksBlockInvalidIfTextIsInvalid(): void
    {
        $block = new SectionBlock();
        $block->setText(new TextObject('text', 'invalid-text-type'));

        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('received "invalid-text-type"');
        $block->validate();
    }

    public function testMarksBlockInvalidIfAccesoryIsInvalid(): void
    {
        $block = new SectionBlock();
        $block->setAccessory(new ImageElement('invalid-url', ''));

        $this->expectException(AssertionFailedException::class);
        $block->validate();
    }

    public function testMarksBlockInvalidIfAtLeastOneFieldIsInvalid(): void
    {
        $block = new SectionBlock();
        $block->addField(new TextObject('text', 'invalid-text-type'));

        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('received "invalid-text-type"');
        $block->validate();
    }

    public function expectedRepresentations(): iterable
    {
        yield 'text-only' => [
            [
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
            ]
        ];

        yield 'fields-only' => [
            [
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
                'type' => 'section',
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
            ]
        ];

        yield 'kitchen-sink' => [
            [
                'text' => [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => 'The section text',
                ],
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
                'accessory' => [
                    'image_url' => 'https://getlaminas.org/images/logo/laminas-foundation-rgb.svg',
                    'alt_text'  => 'the alt text',
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => 'The section text',
                ],
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
                'accessory' => [
                    'type'      => 'image',
                    'image_url' => 'https://getlaminas.org/images/logo/laminas-foundation-rgb.svg',
                    'alt_text'  => 'the alt text',
                ],
            ]
        ];
    }

    /** @dataProvider expectedRepresentations */
    public function testRendersBlockAsExpectedBySlack(array $definition, array $expected): void
    {
        $block = SectionBlock::fromArray($definition);
        $this->assertSame($expected, $block->toArray());
    }
}
