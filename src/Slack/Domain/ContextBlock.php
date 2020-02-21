<?php

declare(strict_types=1);

namespace App\Slack\Domain;

use Assert\Assert;

class ContextBlock implements BlockInterface
{
    use ImageElementValidationTrait;
    use TextObjectValidationTrait;

    /** @var array */
    private $payload;

    public function __construct(array $payload) 
    {
        $this->payload = $payload;
    }

    public function toArray(): array
    {
        return array_merge($this->payload, [
            'type' => 'context',
        ]);
    }

    public function validate(): void
    {
        Assert::that($this->payload)->keyIsset('elements');
        Assert::that($this->payload['elements'])->isArray();
        foreach ($this->payload['elements'] as $element) {
            Assert::that($element)->isArray();
            Assert::that($element)->keyIsset('type');
            Assert::that($element['type'])->string()->inArray(['plain_text', 'mrkdown', 'image']);
            switch ($element['type']) {
                case 'image':
                    $this->validateImageElement($element);
                    break;
                case 'mrkdown':
                case 'plain_text':
                default:
                    $this->validateTextObject($element);
                    break;
            }
        }
    }
}
