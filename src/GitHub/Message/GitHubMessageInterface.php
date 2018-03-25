<?php

declare(strict_types=1);

namespace App\GitHub\Message;

interface GitHubMessageInterface
{
    public function validate() : void;

    public function ignore() : bool;
}
