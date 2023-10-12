<?php

declare(strict_types=1);

namespace AppTest\GitHub\Listener;

use App\GitHub\Event\GitHubRelease;
use App\GitHub\Listener\GitHubReleaseMastodonListener;
use App\Mastodon\MastodonClient;
use Laminas\Twitter\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use RuntimeException;

class GitHubReleaseMastodonListenerTest extends TestCase
{
    use ProphecyTrait;

    /** @var GitHubReleaseMastodonListener */
    private $listener;

    /** @var LoggerInterface|ObjectProphecy */
    private $logger;

    /** @var MastodonClient|ObjectProphecy */
    private $mastodon;

    public function setUp(): void
    {
        $this->mastodon = $this->prophesize(MastodonClient::class);
        $this->logger   = $this->prophesize(LoggerInterface::class);

        $this->listener = new GitHubReleaseMastodonListener(
            $this->mastodon->reveal(),
            $this->logger->reveal()
        );
    }

    public function testReturnsWithoutDoingAnythingIfReleaseIsNotPublished(): void
    {
        $release = new GitHubRelease([
            'release' => [
                'draft' => true,
            ],
        ]);

        $this->mastodon->statusesUpdate(Argument::any())->shouldNotBeCalled();
        $this->logger->error(Argument::any())->shouldNotBeCalled();

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
            ->statusesUpdate(Argument::containingString("laminas/some-component 2.3.4p8\n\nrelease-url"))
            ->willThrow(new RuntimeException('Error tooting release'))
            ->shouldBeCalled();

        $this->logger
            ->error(Argument::containingString('Error tooting release'))
            ->shouldBeCalled();

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

        $updateResponse = $this->prophesize(Response::class);
        $this->mastodon
            ->statusesUpdate(Argument::containingString("laminas/some-component 2.3.4p8\n\nrelease-url"))
            ->will([$updateResponse, 'reveal'])
            ->shouldBeCalled();

        $this->logger->error(Argument::any())->shouldNotBeCalled();

        $this->assertNull($this->listener->__invoke($release));
    }
}
