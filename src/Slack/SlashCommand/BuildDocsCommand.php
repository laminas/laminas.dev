<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use App\GitHub\Event\DocsBuildAction;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;

use function sprintf;
use function trim;

class BuildDocsCommand implements SlashCommandInterface
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
        return 'build-docs';
    }

    public function usage(): string
    {
        return '{repo}';
    }

    public function help(): string
    {
        return 'Trigger a documentation build for the repository described by {repo}.';
    }

    public function dispatch(SlashCommandRequest $request): ResponseInterface
    {
        $repo = trim($request->text());
        $this->dispatcher->dispatch(new DocsBuildAction($repo, $request->responseUrl()));
        return $this->responseFactory->createResponse(sprintf('Documentation build for %s queued', $repo));
    }
}
