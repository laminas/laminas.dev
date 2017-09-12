<?php
declare(strict_types=1);

namespace XtreamLabs\Slack;

class Client
{
    // @web = new WebClient options.token
    public function __construct($token, $endpoint)
    {
    }

    public function connect()
    {
    }

    // @web.chat.postMessage(room, message.text, _.defaults(message, options))
    // @web.chat.postMessage(room, message, options)
    public function send(string $room, Message $message)
    {
    }
}
