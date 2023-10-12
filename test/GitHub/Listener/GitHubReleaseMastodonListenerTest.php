<?php

declare(strict_types=1);

namespace AppTest\GitHub\Listener;

use App\GitHub\Event\GitHubRelease;
use App\GitHub\Listener\GitHubReleaseMastodonListener;
use App\Mastodon\MastodonClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class GitHubReleaseMastodonListenerTest extends TestCase
{
    /** @var GitHubReleaseMastodonListener */
    private $listener;

    /** @var LoggerInterface */
    private $logger;

    /** @var MastodonClient */
    private $mastodon;

    public function setUp(): void
    {
        $this->mastodon = $this->getMockBuilder(MastodonClient::class)->disableOriginalConstructor()->getMock();
        $this->logger   = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->listener = new GitHubReleaseMastodonListener(
            $this->mastodon,
            $this->logger,
            [
                'laminas/ignored-component',
            ]
        );
    }

    public function testReturnsWithoutDoingAnythingIfReleaseIsNotPublished(): void
    {
        $release = new GitHubRelease([
            'release' => [
                'draft' => true,
            ],
        ]);

        $this->mastodon->expects($this->never())->method('statusesUpdate');
        $this->logger->expects($this->never())->method('error');

        $this->assertNull($this->listener->__invoke($release));
    }

    public function testLogsTootUpdateError(): void
    {
        $release = new GitHubRelease([
            'release'    => [
                'draft'    => false,
                'tag_name' => '2.3.4p8',
                'html_url' => 'release-url',
            ],
            'repository' => [
                'full_name' => 'laminas/some-component',
            ],
        ]);

        $this->mastodon
            ->expects($this->once())
            ->method('statusesUpdate')->with("Released: laminas/some-component 2.3.4p8\n\nrelease-url")
            ->willThrowException(new RuntimeException('Error tooting release'));

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Error tooting release laminas/some-component 2.3.4p8: Error tooting release');

        $this->assertNull($this->listener->__invoke($release));
    }

    public function testDoesNotLogWhenMastodonUpdateIsSuccessful(): void
    {
        $release = new GitHubRelease([
            'release'    => [
                'draft'    => false,
                'tag_name' => '2.3.4p8',
                'html_url' => 'release-url',
            ],
            'repository' => [
                'full_name' => 'laminas/some-component',
            ],
        ]);

        $this->mastodon
            ->expects($this->once())
            ->method('statusesUpdate')
            ->with("Released: laminas/some-component 2.3.4p8\n\nrelease-url")
            ->willReturn('foo');

        $this->logger
            ->expects($this->never())
            ->method('error');

        $this->assertNull($this->listener->__invoke($release));
    }

    public function testDoesNotSendWhenPackeIsOnIgnoreList(): void
    {
        $release = new GitHubRelease([
            'release'    => [
                'draft'    => false,
                'tag_name' => '2.3.4p8',
                'html_url' => 'release-url',
            ],
            'repository' => [
                'full_name' => 'laminas/ignored-component',
            ],
        ]);

        $this->mastodon
            ->expects($this->never())
            ->method('statusesUpdate');

        $this->logger
            ->expects($this->never())
            ->method('error');

        $this->assertNull($this->listener->__invoke($release));
    }
}
