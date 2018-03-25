<?php

declare(strict_types=1);

namespace App\Slack\Message;

use Assert\Assert;
use function sprintf;

class DeployMessage
{
    /** @var string */
    private $project;

    /** @var string */
    private $branch;

    /** @var string */
    private $environment;

    public function __construct(string $project, ?string $branch = null, ?string $environment = null)
    {
        $this->project     = $project;
        $this->branch      = $branch ?? 'master';
        $this->environment = $environment ?? 'production';
    }

    public function validateWithConfig(array $config) : void
    {
        Assert::that($config)->keyIsset($this->project);

        $project = $config[$this->project];
        Assert::that($project)->isArray();
        Assert::that($project)->keyIsset('environments');

        $environments = $project['environments'];
        Assert::that($environments)->isArray();
        Assert::that($environments)->keyIsset($this->environment);

        $environment = $environments[$this->environment];
        Assert::that($environment)->keyIsset('host');
        Assert::that($environment['host'])->string();
        Assert::that($environment)->keyIsset('path');
        Assert::that($environment['path'])->string();
    }

    public function getProject() : string
    {
        return $this->project;
    }

    public function getBranch() : string
    {
        return $this->branch;
    }

    public function getEnvironment() : string
    {
        return $this->environment;
    }

    public function createCommandFromConfig(array $config) : string
    {
        $project     = $config[$this->project];
        $environment = $project['environments'][$this->environment];

        $key    = $config['deploy_id_rsa'];
        $user   = 'deploy';
        $host   = $environment['host'];
        $path   = $environment['path'];
        $branch = $this->branch;

        // @codingStandardsIgnoreStart
        return sprintf(
            'ssh -oStrictHostKeyChecking=no -oUserKnownHostsFile=/dev/null -i %s %s@%s "cd %s && make deploy target=%s;"',
            $key,
            $user,
            $host,
            $path,
            $branch
        );
        // @codingStandardsIgnoreEnd
    }
}
