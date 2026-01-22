<?php

namespace App\Tests\Subscriber;

use App\Api\Issue\IssueApi;
use App\Api\Issue\NullIssueApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Model\Repository;
use App\Service\SymfonyVersionProvider;
use App\Subscriber\UnsupportedBranchSubscriber;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;

class UnsupportedBranchSubscriberTest extends TestCase
{
    /**
     * @var IssueApi
     */
    private $issueApi;

    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    protected function setUp(): void
    {
        $this->issueApi = $this->createMock(NullIssueApi::class);
        $symfonyVersionProvider = $this->getMockBuilder(SymfonyVersionProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getMaintainedVersions'])
            ->getMock();
        $symfonyVersionProvider->method('getMaintainedVersions')->willReturn(['4.4', '5.1']);

        $subscriber = new UnsupportedBranchSubscriber($symfonyVersionProvider, $this->issueApi, new NullLogger());
        $this->repository = new Repository('carsonbot-playground', 'symfony', null);

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber($subscriber);
    }

    public function testOnPullRequestOpen()
    {
        $this->issueApi->expects($this->once())
            ->method('commentOnIssue');

        $event = new GitHubEvent([
            'action' => 'opened',
            'pull_request' => [
                'number' => 1234,
                'base' => ['ref' => '4.3'],
            ],
            'repository' => [
                'default_branch' => '5.x',
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();

        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['pull_request']);
        $this->assertSame('4.3', $responseData['unsupported_branch']);
    }

    public function testOnPullRequestOpenDefaultBranch()
    {
        $this->issueApi->expects($this->never())
            ->method('commentOnIssue');

        $event = new GitHubEvent([
            'action' => 'opened',
            'pull_request' => [
                'number' => 1234,
                'base' => ['ref' => '4.4'],
            ],
            'repository' => [
                'default_branch' => '5.x',
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();
        $this->assertEmpty($responseData);
    }

    public function testOnPullRequestNotOpen()
    {
        $this->issueApi->expects($this->never())
            ->method('commentOnIssue');

        $event = new GitHubEvent([
            'action' => 'close',
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();
        $this->assertEmpty($responseData);
    }

    public function testOnPullRequestEditedCleanup()
    {
        $this->issueApi->expects($this->once())
            ->method('findBotComment')
            ->with($this->repository, 1234, 'target one of these branches instead')
            ->willReturn(888);

        $this->issueApi->expects($this->once())
            ->method('removeComment')
            ->with($this->repository, 888);

        $event = new GitHubEvent([
            'action' => 'edited',
            'pull_request' => [
                'number' => 1234,
                'base' => ['ref' => '4.4'],
            ],
            'repository' => [
                'default_branch' => '5.x',
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
    }

    public function testOnPullRequestEditedIdempotency()
    {
        $this->issueApi->expects($this->once())
            ->method('findBotComment')
            ->with($this->repository, 1234, 'target one of these branches instead')
            ->willReturn(888);

        $this->issueApi->expects($this->never())
            ->method('commentOnIssue');

        $event = new GitHubEvent([
            'action' => 'edited',
            'pull_request' => [
                'number' => 1234,
                'base' => ['ref' => '4.3'], // Unsupported branch
            ],
            'repository' => [
                'default_branch' => '5.x',
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
    }
}
