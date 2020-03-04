<?php

declare(strict_types=1);

namespace App\Slack\Domain;

use Assert\AssertionFailedException;

interface ValidatableInterface
{
    /**
     * @throws AssertionFailedException If invalid.
     */
    public function validate(): void;
}
