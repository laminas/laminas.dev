<?php

declare(strict_types=1);

namespace App\Slack\Domain;

use Assert\Assert;
use Assert\AssertionFailedException;

class SectionBlock implements BlockInterface
{
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
            'type' => 'section',
        ]);
    }

    public function validate(): void
    {
        if (! isset($this->payload['text'])
            && ! isset($this->payload['fields'])
        ) {
            throw new AssertionFailedException(
                'Section block requires one or both of the "text" and "blocks" keys; neither provided'
            );
        }

        if (isset($this->payload['text'])) {
            Assert::that($this->payload['text'])->isArray();
            $this->validateTextObject($this->payload['text']);
        }

        if (isset($this->payload['fields'])) {
            Assert::that($this->payload['fields'])->isArray();
            foreach ($this->payload['fields'] as $field) {
                Assert::that($field)->isArray();
                $this->validateTextObject($field);
            }
        }
    }
}
