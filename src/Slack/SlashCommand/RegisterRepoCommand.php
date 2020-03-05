<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use App\GitHub\Event\RegisterWebhook;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;

use function sprintf;
use function trim;

class RegisterRepoCommand implements SlashCommandInterface
{
    use ValidateRepoArgumentTrait;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    public function __construct(
        SlashCommandResponseFactory $responseFactory,
        EventDispatcherInterface $dispatcher
    ) {
        $this->responseFactory = $responseFactory;
        $this->dispatcher      = $dispatcher;
    }

    public function command(): string
    {
        return 'register-repo';
    }

    public function usage(): string
    {
        return '{repo}';
    }

    public function help(): string
    {
        return 'Register the laminas-bot webhook with the repository described by {repo}.';
    }

    public function dispatch(SlashCommandRequest $request): ResponseInterface
    {
        $repo = trim($request->text());
        $this->dispatcher->dispatch(new RegisterWebhook($repo, $request->responseUrl()));
        return $this->responseFactory->createResponse(sprintf(
            'Request to register laminas-bot webhook for %s queued',
            $repo
        ));
    }
}
