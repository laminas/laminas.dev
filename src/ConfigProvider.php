<?php

declare(strict_types=1);

namespace App;

use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Http\Client\Adapter\Curl;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\Stratigility\Middleware\ErrorHandler;
use Laminas\Twitter\Twitter as TwitterClient;
use Mezzio\Application;
use Mezzio\Helper\BodyParams\BodyParamsMiddleware;
use Mezzio\MiddlewareFactory;
use Mezzio\ProblemDetails\ProblemDetailsMiddleware;
use Mezzio\Swoole\Log\SwooleLoggerFactory;
use Phly\EventDispatcher\EventDispatcher;
use Phly\EventDispatcher\ListenerProvider\AttachableListenerProvider;
use Phly\Swoole\TaskWorker\DeferredServiceListenerDelegator;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

use const CURLOPT_SSL_VERIFYHOST;
use const CURLOPT_SSL_VERIFYPEER;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'base_url'     => 'https://laminas.dev',
            'dependencies' => $this->getDependencies(),
            'discourse'    => [
                'url'    => 'https://discourse.laminas.dev',
                'secret' => 'NOT-A-SECRET',
            ],
            'getlaminas'   => [
                'token' => '',
            ],
            'github'       => [
                'token' => '',
            ],
            'monolog'      => [
                'handlers' => [],
            ],
            'slack'        => [
                'channels'       => [
                    'acl'    => '',
                    'github' => '',
                ],
                'signing_secret' => '',
                'token'          => '',
            ],
            'twitter'      => [
                'access_token'        => [
                    'token'  => '',
                    'secret' => '',
                ],
                'oauth_options'       => [
                    'consumerKey'    => '',
                    'consumerSecret' => '',
                ],
                'http_client_options' => [
                    'adapter'     => Curl::class,
                    'curloptions' => [
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_SSL_VERIFYPEER => false,
                    ],
                ],
            ],
        ];
    }

    public function getDependencies(): array
    {
        // phpcs:disable
        return [
            'aliases'             => [
                Slack\SlashCommand\AuthorizedUserListInterface::class => Slack\SlashCommand\AuthorizedUserList::class,
                EventDispatcherInterface::class                       => EventDispatcher::class,
                HttpClientInterface::class                            => HttpClient::class,
                ListenerProviderInterface::class                      => AttachableListenerProvider::class,
                RequestFactoryInterface::class                        => RequestFactory::class,
                ResponseFactoryInterface::class                       => ResponseFactory::class,
                ServerRequestFactoryInterface::class                  => ServerRequestFactory::class,
                StreamFactoryInterface::class                         => StreamFactory::class,
            ],

            'delegators' => [
                Application::class                                         => [Slack\ApplicationDelegatorFactory::class],
                AttachableListenerProvider::class                          => [
                    Discourse\ListenerProviderDelegatorFactory::class,
                    GitHub\ListenerProviderDelegatorFactory::class,
                    Slack\ListenerProviderDelegatorFactory::class,
                ],
                Discourse\Listener\DiscoursePostListener::class            => [DeferredServiceListenerDelegator::class],
                GitHub\Listener\DocsBuildActionListener::class             => [DeferredServiceListenerDelegator::class],
                GitHub\Listener\GitHubIssueListener::class                 => [DeferredServiceListenerDelegator::class],
                GitHub\Listener\GitHubIssueCommentListener::class          => [DeferredServiceListenerDelegator::class],
                GitHub\Listener\GitHubPullRequestListener::class           => [DeferredServiceListenerDelegator::class],
                GitHub\Listener\GitHubReleaseSlackListener::class          => [DeferredServiceListenerDelegator::class],
                GitHub\Listener\GitHubReleaseTweetListener::class          => [DeferredServiceListenerDelegator::class],
                GitHub\Listener\GitHubReleaseWebsiteUpdateListener::class  => [DeferredServiceListenerDelegator::class],
                GitHub\Listener\GitHubStatusListener::class                => [DeferredServiceListenerDelegator::class],
                GitHub\Listener\RegisterWebhookListener::class             => [DeferredServiceListenerDelegator::class],
                Slack\Listener\RegenerateAuthorizedUserListListener::class => [DeferredServiceListenerDelegator::class],
                Slack\Listener\RetweetListener::class                      => [DeferredServiceListenerDelegator::class],
                Slack\Listener\TweetListener::class                        => [DeferredServiceListenerDelegator::class],
            ],

            'factories' => [
                Discourse\Listener\DiscoursePostListener::class               => Discourse\Listener\DiscoursePostListenerFactory::class,
                Discourse\Middleware\DiscourseHandler::class                  => Discourse\Middleware\DiscourseHandlerFactory::class,
                Discourse\Middleware\VerificationMiddleware::class            => Discourse\Middleware\VerificationMiddlewareFactory::class,
                ErrorHandler::class                                           => Factory\ErrorHandlerFactory::class,
                GitHub\GitHubClient::class                                    => GitHub\GitHubClientFactory::class,
                GitHub\Listener\DocsBuildActionListener::class                => GitHub\Listener\DocsBuildActionListenerFactory::class,
                GitHub\Listener\GitHubIssueListener::class                    => GitHub\Listener\GitHubIssueListenerFactory::class,
                GitHub\Listener\GitHubIssueCommentListener::class             => GitHub\Listener\GitHubIssueCommentListenerFactory::class,
                GitHub\Listener\GitHubPullRequestListener::class              => GitHub\Listener\GitHubPullRequestListenerFactory::class,
                GitHub\Listener\GitHubReleaseSlackListener::class             => GitHub\Listener\GitHubReleaseSlackListenerFactory::class,
                GitHub\Listener\GitHubReleaseTweetListener::class             => GitHub\Listener\GitHubReleaseTweetListenerFactory::class,
                GitHub\Listener\GitHubReleaseWebsiteUpdateListener::class     => GitHub\Listener\GitHubReleaseWebsiteUpdateListenerFactory::class,
                GitHub\Listener\GitHubStatusListener::class                   => GitHub\Listener\GitHubStatusListenerFactory::class,
                GitHub\Listener\RegisterWebhookListener::class                => GitHub\Listener\RegisterWebhookListenerFactory::class,
                GitHub\Middleware\GitHubRequestHandler::class                 => GitHub\Middleware\GitHubRequestHandlerFactory::class,
                GitHub\Middleware\VerificationMiddleware::class               => GitHub\Middleware\VerificationMiddlewareFactory::class,
                Handler\ChatHandler::class                                    => Handler\ChatHandlerFactory::class,
                Handler\HomePageHandler::class                                => Handler\HomePageHandlerFactory::class,
                HttpClient::class                                             => Factory\HttpClientFactory::class,
                LoggerInterface::class                                        => Factory\LoggerFactory::class,
                LogMiddleware::class                                          => Factory\LogMiddlewareFactory::class,
                NoopMiddleware::class                                         => InvokableFactory::class,
                ProblemDetailsMiddleware::class                               => Factory\ProblemDetailsMiddlewareFactory::class,
                RequestFactory::class                                         => InvokableFactory::class,
                ResponseFactory::class                                        => InvokableFactory::class,
                ServerRequestFactory::class                                   => InvokableFactory::class,
                Slack\Listener\RegenerateAuthorizedUserListListener::class    => Slack\Listener\RegenerateAuthorizedUserListListenerFactory::class,
                Slack\Listener\RetweetListener::class                         => Slack\Listener\RetweetListenerFactory::class,
                Slack\Listener\TweetListener::class                           => Slack\Listener\TweetListenerFactory::class,
                Slack\Middleware\VerificationMiddleware::class                => Slack\Middleware\VerificationMiddlewareFactory::class,
                Slack\Middleware\SlashCommandHandler::class                   => Slack\Middleware\SlashCommandHandlerFactory::class,
                Slack\SlackClientInterface::class                             => Slack\SlackClientFactory::class,
                Slack\SlashCommand\AuthorizedUserList::class                  => Slack\SlashCommand\AuthorizedUserListFactory::class,
                Slack\SlashCommand\BuildDocsCommand::class                    => Slack\SlashCommand\BuildDocsCommandFactory::class,
                Slack\SlashCommand\RegenerateAuthorizedUserListCommand::class => Slack\SlashCommand\RegenerateAuthorizedUserListCommandFactory::class,
                Slack\SlashCommand\RegisterRepoCommand::class                 => Slack\SlashCommand\RegisterRepoCommandFactory::class,
                Slack\SlashCommand\RetweetCommand::class                      => Slack\SlashCommand\RetweetCommandFactory::class,
                Slack\SlashCommand\SlashCommandResponseFactory::class         => Slack\SlashCommand\SlashCommandResponseFactoryFactory::class,
                Slack\SlashCommand\SlashCommands::class                       => Slack\SlashCommand\SlashCommandsFactory::class,
                Slack\SlashCommand\TweetCommand::class                        => Slack\SlashCommand\TweetCommandFactory::class,
                StreamFactory::class                                          => InvokableFactory::class,
                SwooleLoggerFactory::SWOOLE_LOGGER                            => Factory\AccessLogFactory::class,
                TwitterClient::class                                          => Factory\TwitterClientFactory::class,
                UrlHelper::class                                              => Factory\UrlHelperFactory::class,
            ],
        ];
        // phpcs:enable
    }

    public function registerRoutes(
        Application $app,
        MiddlewareFactory $factory,
        ContainerInterface $container,
        string $basePath = '/'
    ): void {
        $app->get('/', Handler\HomePageHandler::class, 'home');
        $app->get('/chat[/]', Handler\ChatHandler::class, 'chat');

        $debug             = $container->get('config')['debug'] ?? false;
        $initialMiddleware = (bool) $debug
            ? $factory->lazy(LogMiddleware::class)
            : $factory->lazy(NoopMiddleware::class);

        $app->post('/api/discourse/{channel:[a-zA-Z0-9_-]+}/{event:post|topic}', [
            $initialMiddleware,
            ProblemDetailsMiddleware::class,
            Discourse\Middleware\VerificationMiddleware::class,
            BodyParamsMiddleware::class,
            Discourse\Middleware\DiscourseHandler::class,
        ], 'api.discourse');

        $app->post('/api/github', [
            $initialMiddleware,
            ProblemDetailsMiddleware::class,
            GitHub\Middleware\VerificationMiddleware::class,
            BodyParamsMiddleware::class,
            GitHub\Middleware\GitHubRequestHandler::class,
        ], 'api.github');

        $app->post('/api/slack', [
            $initialMiddleware,
            ProblemDetailsMiddleware::class,
            BodyParamsMiddleware::class,
            Slack\Middleware\VerificationMiddleware::class,
            Slack\Middleware\SlashCommandHandler::class,
        ], 'api.slack');
    }
}
