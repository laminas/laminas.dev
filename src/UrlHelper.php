<?php

declare(strict_types=1);

namespace App;

use Mezzio\Helper\UrlHelper as MezzioUrlHelper;

use function ltrim;
use function sprintf;

class UrlHelper
{
    /** @var string */
    private $baseUrl;

    /** @var MezzioUrlHelper */
    private $urlHelper;

    public function __construct(string $baseUrl, MezzioUrlHelper $urlHelper)
    {
        $this->baseUrl   = ltrim($baseUrl, '/');
        $this->urlHelper = $urlHelper;
    }

    public function generate(string $route, array $params = []): string
    {
        return sprintf('%s%s', $this->baseUrl, $this->urlHelper->generate($route, $params));
    }
}
