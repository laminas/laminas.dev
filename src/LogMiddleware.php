<?php

declare(strict_types=1);

namespace App;

use Laminas\Diactoros\Request\Serializer as RequestSerializer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class LogMiddleware implements MiddlewareInterface
{
    /** @var LoggerInterface */
    private $log;

    public function __construct(LoggerInterface $log)
    {
        $this->log = $log;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $this->log->debug(RequestSerializer::toString($request));
        } catch (Throwable $e) {
        }

        $response = $handler->handle($request);

        try {
            $this->log->debug(ResponseSerializer::toString($response));
        } catch (Throwabl $e) {
        }

        return $response;
    }
}
