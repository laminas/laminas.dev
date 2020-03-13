<?php

declare(strict_types=1);

namespace AppTest\Slack\Domain;

use App\Slack\Domain\ContextBlock;
use App\Slack\Domain\TextObject;
use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;

use function array_fill;
use function array_walk;

class ContextBlockTest extends TestCase
{
    public function invalidAmountsOfElements(): iterable
    {
        yield 'zero-elements' => [[]];

        $element = new TextObject('some text');
        yield 'eleven-elements' => [array_fill(0, 11, $element)];
    }

    /** @dataProvider invalidAmountsOfElements */
    public function testInvalidatesWhenCountOfElementsFallsOutsideAcceptableRange(array $elements): void
    {
        $block = new ContextBlock();
        array_walk($elements, function ($element) use ($block) {
            $block->addElement($element);
        });

        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('requires at least 1 and no more than 10 elements');
        $block->validate();
    }

    public function testInvalidatesIfAnyElementIsInvalid(): void
    {
        $label = new TextObject('*Label*');
        $value = new TextObject('Value', 'invalid-text-type');
        $block = new ContextBlock();
        $block->addElement($label);
        $block->addElement($value);

        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('received "invalid-text-type"');
        $block->validate();
    }

    public function testProvidesExpectedRepresentations(): void
    {
        $block = new ContextBlock();
        $label = new TextObject('*Label*');
        $value = new TextObject('Value', TextObject::TYPE_PLAIN_TEXT);
        $block->addElement($label);
        $block->addElement($value);

        $this->assertSame([
            'type'     => 'context',
            'elements' => [
                [
                    'type'     => TextObject::TYPE_MARKDOWN,
                    'text'     => '*Label*',
                    'verbatim' => true,
                ],
                [
                    'type' => TextObject::TYPE_PLAIN_TEXT,
                    'text' => 'Value',
                ],
            ],
        ], $block->toArray());
    }
}
