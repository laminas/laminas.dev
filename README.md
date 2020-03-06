# Laminas-BOT

This repository contains code for Laminas-BOT, which acts as:

- A GitHub webhook handler:
  - Report new issues and pull requests to the Laminas Slack #github channel.
  - Report new issue and pull request commments to the Laminas Slack #github channel.
  - Report repository build status to the Laminas Slack #github channel.
  - Report releases to:
    - The Laminas Slack #github channel.
    - The [@getlaminas|https://twitter.com/getlaminas] twitter account.
    - The https://getlaminas API, to allow rebuilding the release RSS feed.

- A Discourse webhook handler:
  - Report new topics to the category-appropriate channel on the Laminas Slack.
  - Report new topic comments to the category-appropriate channel on the Laminas Slack.

- A Slack slash command handler for the Technical Steering Committee:
  - /build-docs to trigger documentation rebuild for a given repository.
  - /register-repo to register the github webhooks for Laminas-BOT with the
    given repository.
  - /regenerate-tsc-list triggers rebuilding the list of TSC members (the only
    ones authorized to initiate slash commands).

## Architecture

### Handlers

There are three primary endpoints:

- `/api/discourse/[:channel]/:event` is an incoming webhook from Discourse. The
  `:channel` is the name of the Slack channel to notify, and the `:event`
  currently can only be "post". The webhook takes the payload and creates an
  `App\Discourse\Event\DiscoursePost` instance from it, passing that to the
  event dispatcher to handle before emitting a 202 response.

- `/api/github` is an incoming webhook from GitHub. It pulls the event type from
  the `X-GitHub-Event` header and from there creates a `GitHubPullRequest`,
  `GitHubRelease`, `GitHubStatus`, `GitHubIssue`, or `GitHubIssueComment`
  instance before passing it to the event dispatcher to handle. ("ping" events
  result in an immediate return of a 204 response.)

- `/api/slack` is an incoming webhook from Slack, specifically for managing
  slash commands. The payload is used to create a `SlashCommandRequest`, which
  is passed to a `SlashCommands` instance to match to a `SlashCommandInterface`
  implementation and dispatch. Individual implementations marshal appropriate
  events to dispatch via the event dispatcher.

### Events

The repository provides the following event types, with the listed handlers.

Event Type | Handler(s) | Action taken
---------- | ---------- | -----------
`App\Discourse\Event\DiscoursePost` | `App\Discourse\Listener\DiscoursePostListener` | Send a notification to Slack in the appropriate channel of a post creation.
`App\GitHub\Event\DocsBuildAction` | `App\GitHub\Listener\DocsBuildActionListener` | Emit a repository dispatch event on the given repository in order to trigger a documentation rebuild.
`App\GitHub\Event\GitHubIssue` | `App\GitHub\Listener\GitHubIssueListener` | Send a notification to Slack about issue creation or closure.
`App\GitHub\Event\GitHubIssueComment` | `App\GitHub\Listener\GitHubIssueCommentListener` | Send a notification to Slack about comment creation on an issue or pull request.
`App\GitHub\Event\GitHubPullRequest` | `App\GitHub\Listener\GitHubPullRequestListener` | Send a notification to Slack about pull request creation or closure.
`App\GitHub\Event\GitHubRelease` | `App\GitHub\Listener\GitHubRelease\SlackListener`, `App\GitHub\Listener\GitHubReleaseTweetListener`, `App\GitHub\Listener\GitHubReleaseWebsiteUpdateListener` | Send a notification to Slack about release creation; tweet the release details; and notify the website of the release so it can update the release RSS feed.
`App\GitHub\Event\GitHubStatus` | `App\GitHub\Listener\GitHubStatusListener` | Send a notification to Slack about a build failure, error, or success.
`App\GitHub\Event\RegisterWebhook` | `App\GitHub\Listener\RegisterWebhookListener` | Use the GitHub API to register the Laminas-BOT webhook with the given repository.
`App\Slack\Event\RegenerateAuthorizedUserList` | `App\Slack\Listener\RegenerateAuthorizedUserListListener` | Use the Slack Web API to rebuild the list of authorized slash command users from the current #technical-steering-committee list.

All listeners are decorated using the `DeferredListenerDelegator` class from the
phly/phly-swoole-taskworker package. This means that they will be executed via
Swoole task workers at a later time, allowing the various webhooks to return
immediately.

### Helper classes

The package provides the following helper classes to allow performing common
tasks:

Class | Purpose
----- | -------
`App\UrlHelper` | Combines the `ServerUrlHelper` and `UrlHelper` into a single class, simplifying generation of absolute URLs.
`App\HttpClientInterface` | Extends the PSR-17 `RequestFactoryInterface` and adds `send(ServerRequestInterface $request) : ResponseInterface`; allows us to choose the HTTP client implementation.
`App\HttpClient` | Decorates a `GuzzleHttp\Client` and PSR-17 `RequestFactoryInterface` to provide an HTTP client.
`App\GitHub\GitHubClient` | Decorates a `HttpClientInterface`  instance to simplify making API requests to GitHub. Requests generated by the class include the authorization token, and `Accept` and `Content-Type` headers.
`App\Slack\SlackClient` | Decorates a `HttpClientInterface` instance in order to create requests to send to Slack, and marshal the response correctly. Includes methods for sending to a Slack webhook, as well as directly to its Web API.
`App\Slack\SlashCommand\AuthorizedUserList` | Memoizes a list of users authorized to execute slash commands from Slack, based on current membership in the #technical-steering-committee channel.

### Slack messages

The project provides an API for creating Slack messages using Slack's Blocks
API, and the classes providing the support are under the `App\Slack\Domain`
namespace. Implementations include:

- Messages
  - General `Message` content (see https://api.slack.com/reference/messaging/payload)

  - `WebAPIMessage`, for sending messages via the Slack `chat.postMessage`
    endpoint (and related endpoints). This is a normal `Message` payload, with
    the addition of the Slack channel to which you are posting the message. (See
    https://api.slack.com/methods/chat.postMessage#channels)

  - `SlashResponseMessage`, for sending a message to a Slash command webhook
    response URL. This is a normal `Message` payload, with the addition of the
    `response_type` (one of "ephemeral" - the default - or "in_channel"). (See
    https://api.slack.com/interactivity/handling#responses)

- Blocks
  - `ContextBlock` (see https://api.slack.com/reference/block-kit/blocks#context)
  - `SectionBlock` (see https://api.slack.com/reference/block-kit/blocks#context)

- Composition objects
  - `TextObject` (see https://api.slack.com/reference/block-kit/composition-objects#text)

- Element objects
  - `ImageElement` (see https://api.slack.com/reference/block-kit/block-elements#image)

All of these objects support:

- Validation to ensure they have correct structure.
- Casting to the structure required by Slack.

### Security

- GitHub webhooks are expected to have a secret associated that matches the one
  in the production deployment environment of the bot. The secret is used by
  GitHub to create a request signature, and then by the bot to verify the
  signature on receipt of a webhook payload.

- Discourse webhooks are expected to have a secret associated that matches the
  one in the production deployment of the bot. The secret is used by Discourse
  to create a request signature, and then by the bot to verify the signature on
  receipt of a webhook payload.

- Slack provides a [signed secrets](https://api.slack.com/docs/verifying-requests-from-slack) 
  verification method that webhooks can used to validate a request originates
  from Slack. The functionality includes both a request timestamp and the
  signature. Stale timestamps can indicate a replay attack, so we can reject any
  older than a set amount of time. The combination of the timestamp and body are
  used along with a shared secret to determine if the signature is valid.

- Slack slash commands require that the user initiating them is in the list of
  technical steering committee members. If not, an error message is returned to
  the user immediately. The list is generated on application initialization, and
  again on receipt of a /regenerate-tsc-list command.
