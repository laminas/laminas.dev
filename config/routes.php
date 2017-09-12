<?php

/** @var \Zend\Expressive\Application $app */

use XtreamLabs\Http\Api\GitHub\GitHubMiddleware;
use XtreamLabs\Http\Api\Slack\SlackMiddleware;

$app->get('/', XtreamLabs\Http\HomePageAction::class, 'home');
$app->post('/endpoint/github', GitHubMiddleware::class, 'endpoint.github');
$app->post('/endpoint/slack', SlackMiddleware::class, 'endpoint.slack');
