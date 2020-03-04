<?php

declare(strict_types=1);

namespace App\Handler;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

class ChatHandlerFactory
{
    public function __invoke(ContainerInterface $container): ChatHandler
    {
        return new ChatHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(ResponseFactoryInterface::class)
        );
    }
}
