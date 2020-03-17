<?php

declare(strict_types=1);

namespace App\Slack\Domain;

class DividerBlock implements BlockInterface
{
    public static function fromArray(array $data): self
    {
        return new self();
    }

    public function validate(): void
    {
        // no-op
    }

    public function toArray(): array
    {
        return [
            'type' => 'divider',
        ];
    }
}
