<?php

declare(strict_types=1);

namespace App\Slack\Domain;

use DomainException;
use InvalidArgumentException;

interface ValidatableInterface
{
    /**
     * @throws DomainException|InvalidArgumentException if invalid
     */
    public function validate(): void;
}
