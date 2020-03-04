<?php

declare(strict_types=1);

namespace AppTest\Slack\Domain;

use App\Slack\Domain\ContextBlock;
use App\Slack\Domain\Message;
use App\Slack\Domain\SectionBlock;
use App\Slack\Domain\TextObject;
use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    public function testInvalidatesMessageIfNeitherTextNorBlocksArePresent(): void
    {
        $message = new Message();

        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Text must be non-empty and/or one or more blocks must be present');
        $message->validate();
    }

    public function testInvalidatesMessageIfAtLeastOneBlockIsInvalid(): void
    {
        $message = new Message();
        $message->addBlock(new SectionBlock());

        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Section block requires');
        $message->validate();
    }

    public function expectedRepresentations(): iterable
    {
        $message = new Message();
        $message->setText('this is the text');
        yield 'text-only' => [$message, [
            'text' => $message->getText(),
        ]];

        $message = new Message();
        $message->setText('this is the text');
        $message->disableTextMarkdown();
        yield 'text-only-not-markdown' => [$message, [
            'text'   => $message->getText(),
            'mrkdwn' => false,
        ]];

        $message = new Message();
        $message->addBlock(ContextBlock::fromArray([
            'elements' => [
                ['type' => TextObject::TYPE_MARKDOWN, 'text' => 'context text'],
                [
                    'type' => 'image',
                    'image_url' => 'https://getlaminas.org/images/logo/laminas-foundation-rgb.svg',
                    'alt_text' => 'Laminas icon',
                ],
            ],
        ]));
        $message->addBlock(SectionBlock::fromArray([
            'text' => ['type' => TextObject::TYPE_MARKDOWN, 'text' => 'section text']
        ]));
        yield 'blocks-only' => [$message, [
            'blocks' => [
                [
                    'type'     => 'context',
                    'elements' => [
                        ['type' => TextObject::TYPE_MARKDOWN, 'text' => 'context text'],
                        [
                            'type' => 'image',
                            'image_url' => 'https://getlaminas.org/images/logo/laminas-foundation-rgb.svg',
                            'alt_text' => 'Laminas icon',
                        ],
                    ],
                ],
                [
                    'type' => 'section',
                    'text' => ['type' => TextObject::TYPE_MARKDOWN, 'text' => 'section text']
                ],
            ],
        ]];

        $message = new Message();
        $message->setText('this is the text');
        $message->disableTextMarkdown();
        $message->addBlock(ContextBlock::fromArray([
            'elements' => [
                ['type' => TextObject::TYPE_MARKDOWN, 'text' => 'context text'],
                [
                    'type' => 'image',
                    'image_url' => 'https://getlaminas.org/images/logo/laminas-foundation-rgb.svg',
                    'alt_text' => 'Laminas icon',
                ],
            ],
        ]));
        $message->addBlock(SectionBlock::fromArray([
            'text' => ['type' => TextObject::TYPE_MARKDOWN, 'text' => 'section text']
        ]));
        yield 'kitchen-sink' => [$message, [
            'text'   => $message->getText(),
            'mrkdwn' => false,
            'blocks' => [
                [
                    'type'     => 'context',
                    'elements' => [
                        ['type' => TextObject::TYPE_MARKDOWN, 'text' => 'context text'],
                        [
                            'type' => 'image',
                            'image_url' => 'https://getlaminas.org/images/logo/laminas-foundation-rgb.svg',
                            'alt_text' => 'Laminas icon',
                        ],
                    ],
                ],
                [
                    'type' => 'section',
                    'text' => ['type' => TextObject::TYPE_MARKDOWN, 'text' => 'section text']
                ],
            ],
        ]];
    }

    /** @dataProvider expectedRepresentations */
    public function testRendersMessagesAsExpectedBySlack(Message $message, array $expected): void
    {
        $this->assertSame($expected, $message->toArray());
    }
}
