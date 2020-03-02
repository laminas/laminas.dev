<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use Psr\Http\Message\ResponseInterface;

trait ValidateRepoArgumentTrait
{
    /** SlashCommandResponseFactory */
    private $responseFactory;

    public function validate(
        SlashCommandRequest $request,
        AuthorizedUserListInterface $authorizedUsers
    ): ?ResponseInterface {
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
}
