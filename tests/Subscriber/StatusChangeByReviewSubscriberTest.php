<?php

namespace App\Tests\Subscriber;

use App\Api\Status\Status;
use App\Api\Status\StatusApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Model\Repository;
use App\Subscriber\StatusChangeByCommentSubscriber;
use App\Subscriber\StatusChangeByReviewSubscriber;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class StatusChangeByReviewSubscriberTest extends TestCase
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
        $logger = $this->createMock(LoggerInterface::class);
        $this->statusChangeSubscriber = new StatusChangeByReviewSubscriber($this->statusApi, $logger);
        $this->repository = new Repository('weaverryan', 'symfony', null);

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber($this->statusChangeSubscriber);
    }

    /**
     * @dataProvider getCommentsForStatusChange
     */
    public function testOnReview($comment, $expectedStatus)
    {
        if (null !== $expectedStatus) {
            $this->statusApi->expects($this->once())
                ->method('setIssueStatus')
                ->with(1234, $expectedStatus);
        }

        $event = new GitHubEvent([
            'action' => 'submitted',
            'pull_request' => ['number' => 1234, 'user' => ['login' => 'weaverryan']],
            'review' => ['state' => 'commented', 'body' => $comment, 'user' => ['login' => 'leannapelham']],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST_REVIEW);

        $responseData = $event->getResponseData();

        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['pull_request']);
        $this->assertSame($expectedStatus, $responseData['status_change']);
    }

    public function getCommentsForStatusChange()
    {
        return [
            ['Have a great day!', null],
            // basic tests for status change
            ['Status: needs review', Status::NEEDS_REVIEW],
            ['Status: needs work', Status::NEEDS_WORK],
            ['Status: reviewed', Status::REVIEWED],
            // accept quotes
            ['Status: "reviewed"', Status::REVIEWED],
            ["Status: 'reviewed'", Status::REVIEWED],
            // accept trailing punctuation
            ['Status: works for me!', Status::WORKS_FOR_ME],
            ['Status: works for me.', Status::WORKS_FOR_ME],
            // play with different formatting
            ['STATUS: REVIEWED', Status::REVIEWED],
            ['**Status**: reviewed', Status::REVIEWED],
            ['**Status:** reviewed', Status::REVIEWED],
            ['**Status: reviewed**', Status::REVIEWED],
            ['**Status: reviewed!**', Status::REVIEWED],
            ['**Status: reviewed**.', Status::REVIEWED],
            ['Status:reviewed', Status::REVIEWED],
            ['Status:     reviewed', Status::REVIEWED],
            // reject missing colon
            ['Status reviewed', null],
            // multiple matches - use the last one
            ["Status: needs review \r\n that is what the issue *was* marked as.\r\n Status: reviewed", Status::REVIEWED],
            // "needs review" does not come directly after status: , so there is no status change
            ['Here is my status: I\'m really happy! I realize this needs review, but I\'m, having too much fun Googling cats!', null],
            // reject if the status is not on a line of its own
            // use case: someone posts instructions about how to change a status
            // in a comment
            ['You should include e.g. the line `Status: needs review` in your comment', null],
            ['Before the ticket was in state "Status: reviewed", but then the status was changed', null],
        ];
    }

    /**
     * @dataProvider \App\Tests\ValidCommandProvider::get()
     */
    public function testCommandCollision($comment, $subscriber)
    {
        if (StatusChangeByCommentSubscriber::class === $subscriber) {
            $this->statusApi->expects($this->once())->method('setIssueStatus');
        } else {
            $this->statusApi->expects($this->never())->method('setIssueStatus');
        }

        $event = new GitHubEvent([
            'action' => 'submitted',
            'pull_request' => ['number' => 1234, 'user' => ['login' => 'weaverryan']],
            'review' => ['state' => 'commented', 'body' => $comment, 'user' => ['login' => 'leannapelham']],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST_REVIEW);
    }

    public function testOnIssueCommentAuthorSelfReview()
    {
        $this->statusApi->expects($this->never())
            ->method('setIssueStatus')
        ;

        $user = ['login' => 'weaverryan'];

        $event = new GitHubEvent([
            'action' => 'submitted',
            'pull_request' => [
                'number' => 1234,
                'user' => $user,
            ],
            'review' => [
                'state' => 'commented',
                'body' => 'Status: reviewed',
                'user' => $user,
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST_REVIEW);

        $responseData = $event->getResponseData();

        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['pull_request']);
        $this->assertNull($responseData['status_change']);
    }

    public function testOnReviewRequested()
    {
        $this->statusApi->expects($this->once())
            ->method('setIssueStatus')
            ->with(1234, Status::NEEDS_REVIEW);

        $event = new GitHubEvent([
            'action' => 'review_requested',
            'pull_request' => ['number' => 1234],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
    }
}
