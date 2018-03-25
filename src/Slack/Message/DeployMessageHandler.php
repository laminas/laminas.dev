<?php

declare(strict_types=1);

namespace App\Slack\Message;

use App\Slack\Domain\Attachment;
use App\Slack\Domain\AttachmentColor;
use App\Slack\Method\ChatPostMessage;
use App\Slack\SlackClientInterface;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;
use Psr\Log\LoggerInterface;
use Xtreamwayz\Expressive\Messenger\Exception\RejectMessageException;
use function file_get_contents;
use function implode;
use function sprintf;
use function strtok;
use function trim;

class DeployMessageHandler
{
    /** @var SlackClientInterface */
    private $slackClient;

    /** @var array */
    private $config;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(SlackClientInterface $slackClient, LoggerInterface $logger, array $config)
    {
        $this->slackClient = $slackClient;
        $this->logger      = $logger;
        $this->config      = $config;
    }

    public function __invoke(DeployMessage $message) : void
    {
        $this->logger->info('Deploying ' . $message->getProject());
        $project     = $this->config['projects'][$message->getProject()];
        $environment = $project['environments'][$message->getEnvironment()];

        $ssh = new SSH2($environment['host']);
        $ssh->setTimeout(30);
        $key = new RSA();
        $key->loadKey(file_get_contents($this->config['private_key']));
        if (! $ssh->login('deploy', $key)) {
            $title = 'Deploy login failed for ' . $message->getProject();

            $this->logger->error($title, [
                'project'     => $message->getProject(),
                'branch'      => $message->getBranch(),
                'environment' => $message->getEnvironment(),
            ]);

            $this->sendMessage($title, [], AttachmentColor::danger());

            throw new RejectMessageException($title);
        }

        /** @var string|bool $result */
        $result = $ssh->exec(sprintf(
            'cd %s && make deploy target=%s',
            $environment['path'],
            $message->getBranch()
        ));
        if ($result === false) {
            $title = 'Failed deploying ' . $message->getProject();

            $this->logger->error($title, [
                'project'     => $message->getProject(),
                'branch'      => $message->getBranch(),
                'environment' => $message->getEnvironment(),
                'errors'      => implode(', ', $ssh->getErrors()),
            ]);

            $this->sendMessage($title, $ssh->getErrors(), AttachmentColor::danger());

            throw new RejectMessageException($title);
        }

        $title = sprintf(
            'Deployed %s from `%s:%s` to %s',
            $message->getProject(),
            $this->config['projects'][$message->getProject()]['repository'],
            $message->getBranch(),
            $message->getEnvironment()
        );

        $attachment = new Attachment($title, AttachmentColor::success());
        $attachment->withTitle($title);

        $separator = "\r\n";
        $line      = strtok($result, $separator);
        $attachment->addText('```');
        while ($line !== false) {
            $line = trim($line);
            if (! empty($line)) {
                $attachment->addText($line);
            }
            $line = strtok($separator);
        }
        $attachment->addText('```');

        $this->sendAttachment($attachment);
    }

    private function sendMessage(string $title, array $text, ?AttachmentColor $color = null) : void
    {
        $attachment = new Attachment($title, $color ?? AttachmentColor::default());
        $attachment->withTitle($title);
        foreach ($text as $line) {
            $attachment->addText($line);
        }

        $this->sendAttachment($attachment);
    }

    private function sendAttachment(Attachment $attachment) : void
    {
        // Build message
        $apiRequest = (new ChatPostMessage('#server-log'))
            ->addAttachment($attachment);

        $response = $this->slackClient->sendApiRequest($apiRequest);
        if (! $response->isOk()) {
            throw new RejectMessageException($response->getError(), $response->getStatusCode());
        }
    }
}
