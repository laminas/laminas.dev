<?php

declare(strict_types=1);

namespace App\Slack\Response;

use Psr\Http\Message\ResponseInterface;

interface SlackResponseInterface
{
    public function isOk() : bool;

    public function getPayload() : array;

    public function getError() : ?string;

    public function getStatusCode() : int;

    public function getResponse() : ResponseInterface;
}
