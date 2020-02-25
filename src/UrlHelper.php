<?php

declare(strict_types=1);

namespace App;

use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper as MezzioUrlHelper;

class UrlHelper
{
    /** @var ServerUrlHelper */
    private $serverUrlHelper;

    /** @var MezzioUrlHelper */
    private $urlHelper;

    public function __construct(MezzioUrlHelper $urlHelper, ServerUrlHelper $serverUrlHelper)
    {
        $this->urlHelper       = $urlHelper;
        $this->serverUrlHelper = $serverUrlHelper;
    }

    public function generate(string $route, array $params = []): string
    {
        return $this->serverUrlHelper->generate(
            $this->urlHelper->generate($route, $params)
        );
    }
}
