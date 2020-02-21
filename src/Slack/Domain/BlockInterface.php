<?php

declare(strict_types=1);

namespace App\Slack\Domain;

interface BlockInterface
{
    /**
     * Create an array representation of the block
     */
    public function toArray(): array;

    /**
     * Validate the structure of the block
     */
    public function validate(): void;
}
