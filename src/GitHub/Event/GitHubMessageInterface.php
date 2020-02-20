<?php

declare(strict_types=1);

namespace App\GitHub\Event;

interface GitHubMessageInterface
{
    public const GITHUB_ICON = 'https://a.slack-edge.com/2fac/plugins/github/assets/service_36.png';

    public function validate() : void;

    public function ignore() : bool;
}
