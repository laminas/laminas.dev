<?php

declare(strict_types=1);

namespace App;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface HttpClientInterface extends RequestFactoryInterface
{
    public function send(RequestInterface $request): ResponseInterface;
}
