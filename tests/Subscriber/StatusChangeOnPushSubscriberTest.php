<?php

namespace App\Tests\Subscriber;

use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Issues\Status;
use App\Repository\Repository;
use App\Subscriber\StatusChangeOnPushSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use App\Issues\StatusApi;

class StatusChangeOnPushSubscriberTest extends TestCase
{
    private $statusChangeSubscriber;

    private $statusApi;

    private $repository;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;


    protected function setUp()
    {
        $this->statusApi = $this->createMock(StatusApi::class);
        $this->statusChangeSubscriber = new StatusChangeOnPushSubscriber($this->statusApi);
        $this->repository = new Repository('weaverryan', 'symfony', [], null);

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber($this->statusChangeSubscriber);
    }

    /**
     * @dataProvider getStatuses
     */
    public function testOnPushingCommits($currentStatus, $statusChange)
    {
        $this->statusApi->expects($this->any())
            ->method('getIssueStatus')
            ->with(1234, $this->repository)
            ->will($this->returnValue($currentStatus));

        if (null !== $statusChange) {
            $this->statusApi->expects($this->once())
                ->method('setIssueStatus')
                ->with(1234, Status::NEEDS_REVIEW, $this->repository);
        }

        $event = new GitHubEvent([
            'action' => 'synchronize',
            'pull_request' => $this->getPullRequestData(),
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);

        $responseData = $event->getResponseData();

        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['pull_request']);
        $this->assertSame($statusChange, $responseData['status_change']);
    }

    public function getStatuses()
    {
        return [
            [Status::NEEDS_WORK, Status::NEEDS_REVIEW],
            [Status::REVIEWED, null],
            [Status::WORKS_FOR_ME, null],
            [Status::NEEDS_REVIEW, null],
        ];
    }

    public function testOnNonPushPullRequestEvent()
    {
        $this->statusApi->expects($this->any())
            ->method('getIssueStatus')
            ->with(1234, $this->repository)
            ->will($this->returnValue(Status::NEEDS_WORK));

        $event = new GitHubEvent([
            'action' => 'labeled',
            'pull_request' => $this->getPullRequestData(),
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);

        $responseData = $event->getResponseData();

        $this->assertCount(1, $responseData);
        $this->assertSame('labeled', $responseData['unsupported_action']);
    }

    public function testWipPullRequest()
    {
        $this->statusApi->expects($this->any())
            ->method('getIssueStatus')
            ->with(1234, $this->repository)
            ->will($this->returnValue(Status::NEEDS_WORK));

        $event = new GitHubEvent([
            'action' => 'synchronize',
            'pull_request' => $this->getPullRequestData('[wip] needs some more work.'),
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);

        $responseData = $event->getResponseData();

        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['pull_request']);
        $this->assertSame(null, $responseData['status_change']);
    }

    private function getPullRequestData($title = 'Default title.')
    {
        return [
            'number' => 1234,
            'title' => $title,
        ];
    }
}
