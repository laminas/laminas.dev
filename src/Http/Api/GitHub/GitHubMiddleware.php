<?php
declare(strict_types=1);

namespace XtreamLabs\Http\Api\GitHub;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use XtreamLabs\Http\Api\GitHub\Event\PingEventHandler;
use XtreamLabs\Http\Api\GitHub\Event\UnhandledEventHandler;

class GitHubMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, DelegateInterface $delegate) : ResponseInterface
    {
        // TODO: Verify GitHub IP
        // TODO: Check payloadSignature
        // TODO: Check signature

        // Handle event
        // https://github.com/xtreamwayz/xtreambot/blob/master/src/api/github.js

        $event = 'ping';
        $payload = [];

        switch ($event) {
            case 'ping':
                $handler = new PingEventHandler();
                break;

            case 'push':                // TODO: GitHub push event
            case 'status':              // TODO: GitHub status event
            case 'pull_request':        // TODO: GitHub pull_request event
            case 'deployment':          // TODO: GitHub deployment event
            case 'deployment_status':   // TODO: GitHub deployment_status event
            default:
                $handler = new UnhandledEventHandler();
                break;
        }

        return $handler($request, $payload);
    }
}
