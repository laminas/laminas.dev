<?php

declare(strict_types=1);

namespace App\Slack;

use App\Slack\Method\ApiRequestInterface;
use App\Slack\Response\SlackResponseInterface;
use Psr\Http\Message\RequestInterface;

interface SlackClientInterface
{
    public function getDefaultChannel() : string;

    public function send(RequestInterface $request) : SlackResponseInterface;

    public function sendApiRequest(ApiRequestInterface $method) : SlackResponseInterface;
}
