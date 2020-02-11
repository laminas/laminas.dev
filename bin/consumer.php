<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Xtreamwayz\Mezzio\Messenger\Command\CommandQueueWorker;

chdir(__DIR__ . '/../');
require 'vendor/autoload.php';

/** @var ContainerInterface $container */
$container = require 'config/container.php';

$app = new Application('Mezzio Console');
$app->add($container->get(CommandQueueWorker::class));
$app->run();
