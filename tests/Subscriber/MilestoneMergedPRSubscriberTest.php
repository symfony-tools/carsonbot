<?php

namespace App\Tests\Subscriber;

use App\Api\Milestone\GithubMilestoneApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Model\Repository;
use App\Subscriber\MilestoneMergedPRSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class MilestoneMergedPRSubscriberTest extends TestCase
{
    private $subscriber;

    private $milestonesApi;

    private $repository;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    protected function setUp(): void
    {
        $this->milestonesApi = $this->createMock(GithubMilestoneApi::class);
        $this->subscriber = new MilestoneMergedPRSubscriber($this->milestonesApi);
        $this->repository = new Repository('nyholm', 'symfony', null);

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber($this->subscriber);
    }

    public function testOnPullRequestMerged()
    {
        $this->milestonesApi->expects($this->once())
            ->method('exists')
            ->with($this->repository, '4.4')
            ->willReturn(true);

        $this->milestonesApi->expects($this->once())
            ->method('updateMilestone')
            ->with($this->repository, 1234, '4.4');

        $event = new GitHubEvent([
            'action' => 'closed',
            'merged' => true,
            'milestone' => '1.2',
            'pull_request' => [
                'number' => 1234,
                'base' => ['ref' => '4.4'],
            ],
            'repository' => [
                'default_branch' => 'master',
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();

        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['pull_request']);
        $this->assertSame('4.4', $responseData['milestone']);
    }

    public function testMilestoneNotExist()
    {
        $this->milestonesApi->expects($this->once())
            ->method('exists')
            ->with($this->repository, '4.4')
            ->willReturn(false);

        $this->milestonesApi->expects($this->never())
            ->method('updateMilestone');

        $event = new GitHubEvent([
            'action' => 'closed',
            'merged' => true,
            'milestone' => null,
            'pull_request' => [
                'number' => 1234,
                'base' => ['ref' => '4.4'],
            ],
            'repository' => [
                'default_branch' => 'master',
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();
        $this->assertEmpty($responseData);
    }

    public function testOnPullRequestNotMerged()
    {
        $this->milestonesApi->expects($this->never())
            ->method('updateMilestone');

        $event = new GitHubEvent([
            'action' => 'closed',
            'merged' => false,
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();
        $this->assertEmpty($responseData);
    }
}
