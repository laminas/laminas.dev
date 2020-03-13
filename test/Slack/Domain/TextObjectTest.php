<?php

declare(strict_types=1);

namespace AppTest\Slack\Domain;

use App\Slack\Domain\TextObject;
use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;

class TextObjectTest extends TestCase
{
    public function testInvalidatesTextObjectIfTypeIsUnknown(): void
    {
        $text = new TextObject('text', 'invalid-text-type');

        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('either "plain_text" or "mrkdwn"; received "invalid-text-type"');
        $text->validate();
    }

    public function expectedRepresentations(): iterable
    {
        yield 'markdown' => [
            ['type' => TextObject::TYPE_MARKDOWN, 'text' => 'some text'],
            ['type' => TextObject::TYPE_MARKDOWN, 'text' => 'some text', 'verbatim' => true],
        ];

        yield 'plain-text' => [
            ['type' => TextObject::TYPE_PLAIN_TEXT, 'text' => 'some text'],
            ['type' => TextObject::TYPE_PLAIN_TEXT, 'text' => 'some text'],
        ];

        yield 'markdown-escape-emoji' => [
            ['type' => TextObject::TYPE_MARKDOWN, 'text' => 'some text', 'emoji' => false],
            ['type' => TextObject::TYPE_MARKDOWN, 'text' => 'some text', 'verbatim' => true],
        ];

        yield 'plain-text-escape-emoji' => [
            ['type' => TextObject::TYPE_PLAIN_TEXT, 'text' => 'some text', 'emoji' => false],
            ['type' => TextObject::TYPE_PLAIN_TEXT, 'text' => 'some text', 'emoji' => false],
        ];

        yield 'markdown-non-verbatim' => [
            ['type' => TextObject::TYPE_MARKDOWN, 'text' => 'some text', 'verbatim' => false],
            ['type' => TextObject::TYPE_MARKDOWN, 'text' => 'some text'],
        ];

        yield 'plain-text-verbatim' => [
            ['type' => TextObject::TYPE_PLAIN_TEXT, 'text' => 'some text', 'verbatim' => true],
            ['type' => TextObject::TYPE_PLAIN_TEXT, 'text' => 'some text'],
        ];
    }

    /** @dataProvider expectedRepresentations */
    public function testRendersAsExpectedBySlack(array $definition, array $expected): void
    {
        $text = TextObject::fromArray($definition);
        $this->assertSame($expected, $text->toArray());
    }
}
