<?php

declare(strict_types=1);

namespace AppTest\GitHub\Listener;

use App\GitHub\Event\GitHubRelease;
use App\GitHub\Listener\GitHubReleaseTweetListener;
use Laminas\Twitter\Response;
use Laminas\Twitter\Twitter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class GitHubReleaseTweetListenerTest extends TestCase
{
    use ProphecyTrait;

    /** @var GitHubReleaseTweetListener */
    private $listener;

    /** @var LoggerInterface|ObjectProphecy */
    private $logger;

    /** @var Twitter|ObjectProphecy */
    private $twitter;

    public function setUp(): void
    {
        $this->twitter = $this->prophesize(Twitter::class);
        $this->logger  = $this->prophesize(LoggerInterface::class);

        $this->listener = new GitHubReleaseTweetListener(
            $this->twitter->reveal(),
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

        $this->twitter->accountVerifyCredentials()->shouldNotBeCalled();
        $this->twitter->statusesUpdate(Argument::any())->shouldNotBeCalled();
        $this->logger->error(Argument::any())->shouldNotBeCalled();

        $this->assertNull($this->listener->__invoke($release));
    }

    public function testLogsErrorWithoutTweetingIfUnableToVerifyTwitterClient(): void
    {
        $release = new GitHubRelease([
            'release'    => [
                'draft'    => false,
                'tag_name' => '2.3.4p8',
            ],
            'repository' => [
                'full_name' => 'laminas/some-component',
            ],
        ]);

        $response = $this->prophesize(Response::class);
        $response->isSuccess()->willReturn(false)->shouldBeCalled();
        $this->twitter->accountVerifyCredentials()->will([$response, 'reveal'])->shouldBeCalled();
        $this->twitter->statusesUpdate(Argument::any())->shouldNotBeCalled();

        $this->logger
            ->error(Argument::containingString('Could not validate twitter credentials'))
            ->shouldBeCalled();

        $this->assertNull($this->listener->__invoke($release));
    }

    public function testLogsTwitterUpdateError(): void
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

        $verificationResponse = $this->prophesize(Response::class);
        $verificationResponse->isSuccess()->willReturn(true)->shouldBeCalled();
        $this->twitter->accountVerifyCredentials()->will([$verificationResponse, 'reveal'])->shouldBeCalled();

        $updateResponse = $this->prophesize(Response::class);
        $updateResponse->isError()->willReturn(true)->shouldBeCalled();
        $updateResponse->getErrors()->willReturn([]);
        $this->twitter
            ->statusesUpdate(Argument::containingString("laminas/some-component 2.3.4p8\n\nrelease-url"))
            ->will([$updateResponse, 'reveal'])
            ->shouldBeCalled();

        $this->logger
            ->error(Argument::containingString('Error tweeting release'))
            ->shouldBeCalled();

        $this->assertNull($this->listener->__invoke($release));
    }

    public function testDoesNotLogWhenTwitterUpdateIsSuccessful(): void
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

        $verificationResponse = $this->prophesize(Response::class);
        $verificationResponse->isSuccess()->willReturn(true)->shouldBeCalled();
        $this->twitter->accountVerifyCredentials()->will([$verificationResponse, 'reveal'])->shouldBeCalled();

        $updateResponse = $this->prophesize(Response::class);
        $updateResponse->isError()->willReturn(false)->shouldBeCalled();
        $updateResponse->getErrors()->shouldNotBeCalled();
        $this->twitter
            ->statusesUpdate(Argument::containingString("laminas/some-component 2.3.4p8\n\nrelease-url"))
            ->will([$updateResponse, 'reveal'])
            ->shouldBeCalled();

        $this->logger->error(Argument::any())->shouldNotBeCalled();

        $this->assertNull($this->listener->__invoke($release));
    }
}
