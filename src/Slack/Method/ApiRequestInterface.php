<?php

declare(strict_types=1);

namespace App\Slack\Method;

interface ApiRequestInterface
{
    public function getMethod() : string;

    public function getEndpoint() : string;

    public function toArray() : ?array;
}
