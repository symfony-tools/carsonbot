<?php

namespace App\Tests\Subscriber;

use App\Api\Status\Status;
use App\Api\Status\StatusApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Model\Repository;
use App\Subscriber\StatusChangeOnPushSubscriber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class StatusChangeOnPushSubscriberTest extends TestCase
{
    private $statusChangeSubscriber;

    private $statusApi;

    private $repository;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    protected function setUp(): void
    {
        $this->statusApi = $this->createMock(StatusApi::class);
        $this->statusChangeSubscriber = new StatusChangeOnPushSubscriber($this->statusApi);
        $this->repository = new Repository('weaverryan', 'symfony', null);

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber($this->statusChangeSubscriber);
    }

    #[DataProvider('getStatuses')]
    public function testOnPushingCommits($currentStatus, $statusChange)
    {
        $this->statusApi->expects($this->any())
            ->method('getIssueStatus')
            ->with(1234, $this->repository)
            ->willReturn($currentStatus);

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

    /**
     * @return iterable<array{string, string|null}>
     */
    public static function getStatuses(): iterable
    {
        yield [Status::NEEDS_WORK, Status::NEEDS_REVIEW];
        yield [Status::REVIEWED, null];
        yield [Status::WORKS_FOR_ME, null];
        yield [Status::NEEDS_REVIEW, null];
    }

    public function testOnNonPushPullRequestEvent()
    {
        $this->statusApi->expects($this->any())
            ->method('getIssueStatus')
            ->with(1234, $this->repository)
            ->willReturn(Status::NEEDS_WORK);

        $event = new GitHubEvent([
            'action' => 'labeled',
            'pull_request' => $this->getPullRequestData(),
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);

        $responseData = $event->getResponseData();

        $this->assertEmpty($responseData);
    }

    public function testWipPullRequest()
    {
        $this->statusApi->expects($this->any())
            ->method('getIssueStatus')
            ->with(1234, $this->repository)
            ->willReturn(Status::NEEDS_WORK);

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
