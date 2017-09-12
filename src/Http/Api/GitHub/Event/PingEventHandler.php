<?php
declare(strict_types=1);

namespace XtreamLabs\Http\Api\GitHub\Event;

use Psr\Http\Message\ServerRequestInterface;
use XtreamLabs\Http\Api\ApiEventHandler;
use Zend\Diactoros\Response\JsonResponse;

class PingEventHandler implements ApiEventHandler
{
    public function __invoke(ServerRequestInterface $request, array $payload) : JsonResponse
    {
        return new JsonResponse([
            'message' => 'Hello from XtreamLabs. :D',
        ], 204);
    }
}
