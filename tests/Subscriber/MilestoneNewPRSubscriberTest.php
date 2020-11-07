<?php

namespace App\Tests\Subscriber;

use App\Api\Milestone\MilestonesApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Model\Repository;
use App\Service\SymfonyVersionProvider;
use App\Subscriber\MilestoneNewPRSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class MilestoneNewPRSubscriberTest extends TestCase
{
    private $subscriber;

    private $milestonesApi;

    private $repository;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    protected function setUp()
    {
        $this->milestonesApi = $this->createMock(MilestonesApi::class);
        $symfonyVersionProvider = $this->getMockBuilder(SymfonyVersionProvider::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCurrentVersion'])
            ->getMock();
        $symfonyVersionProvider->method('getCurrentVersion')->willReturn('5.1');
        $this->subscriber = new MilestoneNewPRSubscriber($this->milestonesApi, $symfonyVersionProvider);
        $this->repository = new Repository('nyholm', 'symfony', null);

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber($this->subscriber);
    }

    public function testOnPullRequestOpen()
    {
        $this->milestonesApi->expects($this->once())
            ->method('exists')
            ->with($this->repository, '4.4')
            ->willReturn(true);

        $this->milestonesApi->expects($this->once())
            ->method('updateMilestone')
            ->with($this->repository, 1234, '4.4');

        $event = new GitHubEvent([
            'action' => 'opened',
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

    public function testOnPullRequestOpenDefaultBranch()
    {
        $this->milestonesApi->expects($this->never())
            ->method('updateMilestone');

        $event = new GitHubEvent([
            'action' => 'opened',
            'pull_request' => [
                'number' => 1234,
                'base' => ['ref' => 'master'],
            ],
            'repository' => [
                'default_branch' => 'master',
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();
        $this->assertEmpty($responseData);
    }

    public function testOnPullRequestOpenMilestoneNotExist()
    {
        $this->milestonesApi->expects($this->once())
            ->method('exists')
            ->with($this->repository, '4.4')
            ->willReturn(false);

        $this->milestonesApi->expects($this->never())
            ->method('updateMilestone');

        $event = new GitHubEvent([
            'action' => 'opened',
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

    public function testOnPullRequestNotOpen()
    {
        $this->milestonesApi->expects($this->never())
            ->method('updateMilestone');

        $event = new GitHubEvent([
            'action' => 'close',
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();
        $this->assertEmpty($responseData);
    }
}
