<?php

namespace App\Tests\Subscriber;

use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Issues\Status;
use App\Repository\Repository;
use App\Subscriber\StatusChangeByCommentSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use App\Issues\StatusApi;
use Psr\Log\LoggerInterface;

class StatusChangeByCommentSubscriberTest extends TestCase
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
        $logger = $this->createMock(LoggerInterface::class);
        $this->statusChangeSubscriber = new StatusChangeByCommentSubscriber($this->statusApi, $logger);
        $this->repository = new Repository('weaverryan', 'symfony', [], null);

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber($this->statusChangeSubscriber);
    }

    /**
     * @dataProvider getCommentsForStatusChange
     */
    public function testOnIssueComment($comment, $expectedStatus)
    {
        if (null !== $expectedStatus) {
            $this->statusApi->expects($this->once())
                ->method('setIssueStatus')
                ->with(1234, $expectedStatus);
        }

        $event = new GitHubEvent([
            'issue' => ['number' => 1234, 'user' => ['login' => 'weaverryan']],
            'comment' => ['body' => $comment, 'user' => ['login' => 'leannapelham']],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::ISSUE_COMMENT);

        $responseData = $event->getResponseData();

        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['issue']);
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
     * @dataProvider getCommentsForStatusChange
     */
    public function testOnIssueCommentAuthorSelfReview()
    {
        $this->statusApi->expects($this->never())
            ->method('setIssueStatus')
        ;

        $user = ['login' => 'weaverryan'];

        $event = new GitHubEvent([
            'issue' => [
                'number' => 1234,
                'user' => $user,
            ],
            'comment' => [
                'body' => 'Status: reviewed',
                'user' => $user,
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::ISSUE_COMMENT);

        $responseData = $event->getResponseData();

        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['issue']);
        $this->assertNull($responseData['status_change']);
    }
}
