<?php

declare(strict_types=1);

namespace AppTest\Slack\Middleware;

use App\Slack\Middleware\DeployHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Zend\Diactoros\ServerRequest;
use function sprintf;

class DeployHandlerTest extends TestCase
{
    /** @dataProvider validCommandsProvider */
    public function testValidDeployCommands(string $project, string $branch) : void
    {
        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withParsedBody([
                'token'       => 'gIkuvaNzQIHg97ATvDxqgjtO',
                'team_id'     => 'T0001',
                'team_domain' => 'example',
                'command'     => '/deploy',
                'text'        => sprintf('%s %s', $project, $branch),
            ]);

        $bus      = $this->prophesize(MessageBusInterface::class);
        $handler  = new DeployHandler(
            $bus->reveal(),
            [
                'project'     => [],
                'project.com' => [],
                'project-com' => [],
                'project_com' => [],
            ]
        );
        $response = $handler->handle($request);

        self::assertInstanceOf(ResponseInterface::class, $response);
    }

    public function validCommandsProvider() : array
    {
        return [
            ['project', 'branch'],
            ['project.com', 'branch-dev'],
            ['project-com', 'feature/something-awesome'],
            ['project_com', 'feature/something_awesome'],
        ];
    }
}
