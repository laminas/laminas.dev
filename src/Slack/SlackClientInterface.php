<?php

declare(strict_types=1);

namespace App\Slack;

use App\Slack\Domain\SlashResponseMessage;
use App\Slack\Domain\WebAPIMessage;
use App\Slack\Method\ApiRequestInterface;
use App\Slack\Response\SlackResponseInterface;
use Psr\Http\Message\RequestInterface;

interface SlackClientInterface
{
    public function send(RequestInterface $request): SlackResponseInterface;

    public function sendWebAPIMessage(WebAPIMessage $message): ?SlackResponseInterface;

    public function sendWebhookMessage(string $url, SlashResponseMessage $message): ?SlackResponseInterface;
}
