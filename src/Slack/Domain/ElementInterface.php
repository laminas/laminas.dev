<?php

declare(strict_types=1);

namespace App\Slack\Domain;

interface ElementInterface extends
    RenderableInterface,
    ValidatableInterface
{
}
