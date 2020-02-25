<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use App\GitHub\Event\DocsBuildAction;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;

class BuildDocsCommand implements SlashCommandInterface
{
    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** SlashCommandResponseFactory */
    private $responseFactory;

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

    public function validate(SlashCommandRequest $request, AuthorizedUserList $authorizedUsers): ?ResponseInterface
    {
        if (! $authorizedUsers->isAuthorized($request->userId())) {
            return $this->responseFactory->createUnauthorizedResponse();
        }

        $argument = trim($request->text());
        if (! preg_match('#^[a-z0-9_-]+/[a-z0-9_-]+$#', $argument)) {
            return $this->responseFactory->createResponse(
                'Repository argument MUST be of form org/repo, and consist of only'
                . ' lowercase letters, digits, underscores, and dashes.'
            );
        }

        list($org, $repo) = explode('/', $argument, 2);

        if (! in_array($org, ['laminas', 'laminas-api-tools', 'mezzio'], true)) {
            return $this->responseFactory->createResponse(
                'Organization part of repository MUST be one of laminas, '
                . ' laminas-api-tools, or mezzio.'
            );
        }

        return null;
    }

    public function dispatch(SlashCommandRequest $request): ResponseInterface
    {
        $repo = trim($request->text());
        $this->dispatcher->dispatch(new DocsBuildAction($repo));
        return $this->responseFactory->createResponse(sprintf('Documentation build for %s queued', $repo));
    }
}
