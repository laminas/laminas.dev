<?php

declare(strict_types=1);

namespace App\Slack\Domain;

interface RenderableInterface
{
    /**
     * Create and return an array representation of the item.
     */
    public function toArray(): array;
}
