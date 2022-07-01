<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\HomePageHandler;
use App\Handler\HomePageHandlerFactory;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

class HomePageHandlerFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testFactoryWithTemplate(): void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container
            ->get(TemplateRendererInterface::class)
            ->willReturn($this->prophesize(TemplateRendererInterface::class));

        $container
            ->get(ResponseFactoryInterface::class)
            ->willReturn($this->prophesize(ResponseFactoryInterface::class));

        $factory = new HomePageHandlerFactory();

        $homePage = $factory($container->reveal());

        self::assertInstanceOf(HomePageHandler::class, $homePage);
    }
}
