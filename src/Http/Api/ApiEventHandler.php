<?php
declare(strict_types=1);

namespace XtreamLabs\Http\Api;

use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

interface ApiEventHandler
{
    public function __invoke(ServerRequestInterface $request, array $payload) : JsonResponse;
}
