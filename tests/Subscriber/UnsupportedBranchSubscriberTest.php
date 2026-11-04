<?php

namespace App\Tests\Subscriber;

use App\Api\Issue\IssueApi;
use App\Api\Issue\NullIssueApi;
use App\Api\Milestone\MilestoneApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Model\Repository;
use App\Service\SymfonyVersionProvider;
use App\Subscriber\UnsupportedBranchSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
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

    private SymfonyVersionProvider|MockObject $symfonyVersionProvider;

    private MilestoneApi|MockObject $milestoneApi;

    protected function setUp(): void
    {
        $this->issueApi = $this->createMock(NullIssueApi::class);
        $this->symfonyVersionProvider = $this->getMockBuilder(SymfonyVersionProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getMaintainedVersions'])
            ->getMock();
        $this->symfonyVersionProvider->method('getMaintainedVersions')->willReturn(['4.4', '5.1']);

        $this->milestoneApi = $this->createMock(MilestoneApi::class);

        $subscriber = new UnsupportedBranchSubscriber($this->symfonyVersionProvider, $this->issueApi, $this->milestoneApi, new NullLogger());
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

    public function testOnPullRequestOpenTargetingDefaultBranchDoesNotFetchVersions()
    {
        $this->symfonyVersionProvider->expects($this->never())->method('getMaintainedVersions');
        $this->issueApi->expects($this->never())->method('findBotComment');
        $this->issueApi->expects($this->never())->method('commentOnIssue');

        $event = new GitHubEvent([
            'action' => 'opened',
            'pull_request' => [
                'number' => 1234,
                'base' => ['ref' => '5.x'],
            ],
            'repository' => [
                'default_branch' => '5.x',
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $this->assertEmpty($event->getResponseData());
    }

    public function testOnPullRequestOpenByMemberTargetingMilestone()
    {
        $this->milestoneApi->expects($this->once())
            ->method('exists')
            ->with($this->repository, '4.3')
            ->willReturn(true);

        $this->issueApi->expects($this->never())
            ->method('commentOnIssue');

        $event = new GitHubEvent([
            'action' => 'opened',
            'pull_request' => [
                'number' => 1234,
                'author_association' => 'MEMBER',
                'base' => ['ref' => '4.3'],
            ],
            'repository' => [
                'default_branch' => '5.x',
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $this->assertEmpty($event->getResponseData());
    }

    public function testOnPullRequestOpenByMemberWithoutMilestone()
    {
        $this->milestoneApi->expects($this->once())
            ->method('exists')
            ->with($this->repository, '4.3')
            ->willReturn(false);

        $this->issueApi->expects($this->once())
            ->method('commentOnIssue');

        $event = new GitHubEvent([
            'action' => 'opened',
            'pull_request' => [
                'number' => 1234,
                'author_association' => 'MEMBER',
                'base' => ['ref' => '4.3'],
            ],
            'repository' => [
                'default_branch' => '5.x',
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $this->assertSame('4.3', $event->getResponseData()['unsupported_branch']);
    }

    public function testOnPullRequestOpenByContributorTargetingMilestone()
    {
        $this->milestoneApi->method('exists')->willReturn(true);

        $this->issueApi->expects($this->once())
            ->method('commentOnIssue');

        $event = new GitHubEvent([
            'action' => 'opened',
            'pull_request' => [
                'number' => 1234,
                'author_association' => 'CONTRIBUTOR',
                'base' => ['ref' => '4.3'],
            ],
            'repository' => [
                'default_branch' => '5.x',
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $this->assertSame('4.3', $event->getResponseData()['unsupported_branch']);
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
            ->willReturn('IC_kwNodeId888');

        $this->issueApi->expects($this->once())
            ->method('minimizeComment')
            ->with('IC_kwNodeId888');

        $event = new GitHubEvent([
            'action' => 'edited',
            'changes' => ['base' => ['ref' => ['from' => '4.3'], 'sha' => ['from' => 'abc123']]],
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

    public function testOnPullRequestEditedToDefaultBranchCleanup()
    {
        $this->symfonyVersionProvider->expects($this->never())->method('getMaintainedVersions');

        $this->issueApi->expects($this->once())
            ->method('findBotComment')
            ->with($this->repository, 1234, 'target one of these branches instead')
            ->willReturn('IC_kwNodeId888');

        $this->issueApi->expects($this->once())
            ->method('minimizeComment')
            ->with('IC_kwNodeId888');

        $event = new GitHubEvent([
            'action' => 'edited',
            'changes' => ['base' => ['ref' => ['from' => '4.3'], 'sha' => ['from' => 'abc123']]],
            'pull_request' => [
                'number' => 1234,
                'base' => ['ref' => '5.x'],
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
            ->willReturn('IC_kwNodeId888');

        $this->issueApi->expects($this->never())
            ->method('commentOnIssue');

        $event = new GitHubEvent([
            'action' => 'edited',
            'changes' => ['base' => ['ref' => ['from' => '4.4'], 'sha' => ['from' => 'abc123']]],
            'pull_request' => [
                'number' => 1234,
                'base' => ['ref' => '4.3'],
            ],
            'repository' => [
                'default_branch' => '5.x',
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
    }

    public function testOnPullRequestEditedWithoutBaseChangeIsIgnored()
    {
        $this->issueApi->expects($this->never())->method('findBotComment');
        $this->issueApi->expects($this->never())->method('commentOnIssue');

        $event = new GitHubEvent([
            'action' => 'edited',
            'changes' => ['title' => ['from' => 'Old title']],
            'pull_request' => [
                'number' => 1234,
                'base' => ['ref' => '4.3'],
            ],
            'repository' => [
                'default_branch' => '5.x',
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
    }
}
