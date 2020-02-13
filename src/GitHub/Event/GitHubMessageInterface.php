<?php

declare(strict_types=1);

namespace App\GitHub\Event;

interface GitHubMessageInterface
{
    public function validate() : void;

    public function ignore() : bool;
}
