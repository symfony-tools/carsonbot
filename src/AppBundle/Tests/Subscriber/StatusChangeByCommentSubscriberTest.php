<?php

namespace AppBundle\Tests\Subscriber;

use AppBundle\Event\GitHubEvent;
use AppBundle\GitHubEvents;
use AppBundle\Issues\Status;
use AppBundle\Repository\Repository;
use AppBundle\Subscriber\StatusChangeByCommentSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcher;

class StatusChangeByCommentSubscriberTest extends \PHPUnit_Framework_TestCase
{
    private $statusChangeSubscriber;

    private $statusApi;

    private $repository;

    /**
     * @var EventDispatcher
     */
    private static $dispatcher;

    public static function setUpBeforeClass()
    {
        self::$dispatcher = new EventDispatcher();
    }

    protected function setUp()
    {
        $this->statusApi = $this->getMock('AppBundle\Issues\StatusApi');
        $logger = $this->getMock('Psr\Log\LoggerInterface');
        $this->statusChangeSubscriber = new StatusChangeByCommentSubscriber($this->statusApi, $logger);
        $this->repository = new Repository('weaverryan', 'symfony', [], null);

        self::$dispatcher->addSubscriber($this->statusChangeSubscriber);
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

        $event = new GitHubEvent(array(
            'issue' => array('number' => 1234, 'user' => ['login' => 'weaverryan']),
            'comment' => array('body' => $comment, 'user' => ['login' => 'leannapelham']),
        ), $this->repository);

        self::$dispatcher->dispatch(GitHubEvents::ISSUE_COMMENT, $event);

        $responseData = $event->getResponseData();

        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['issue']);
        $this->assertSame($expectedStatus, $responseData['status_change']);
    }

    public function getCommentsForStatusChange()
    {
        return array(
            array('Have a great day!', null),
            // basic tests for status change
            array('Status: needs review', Status::NEEDS_REVIEW),
            array('Status: needs work', Status::NEEDS_WORK),
            array('Status: reviewed', Status::REVIEWED),
            // accept quotes
            array('Status: "reviewed"', Status::REVIEWED),
            array("Status: 'reviewed'", Status::REVIEWED),
            // accept trailing punctuation
            array('Status: works for me!', Status::WORKS_FOR_ME),
            array('Status: works for me.', Status::WORKS_FOR_ME),
            // play with different formatting
            array('STATUS: REVIEWED', Status::REVIEWED),
            array('**Status**: reviewed', Status::REVIEWED),
            array('**Status:** reviewed', Status::REVIEWED),
            array('**Status: reviewed**', Status::REVIEWED),
            array('**Status: reviewed!**', Status::REVIEWED),
            array('**Status: reviewed**.', Status::REVIEWED),
            array('Status:reviewed', Status::REVIEWED),
            array('Status:     reviewed', Status::REVIEWED),
            // reject missing colon
            array('Status reviewed', null),
            // multiple matches - use the last one
            array("Status: needs review \r\n that is what the issue *was* marked as.\r\n Status: reviewed", Status::REVIEWED),
            // "needs review" does not come directly after status: , so there is no status change
            array('Here is my status: I\'m really happy! I realize this needs review, but I\'m, having too much fun Googling cats!', null),
            // reject if the status is not on a line of its own
            // use case: someone posts instructions about how to change a status
            // in a comment
            array('You should include e.g. the line `Status: needs review` in your comment', null),
            array('Before the ticket was in state "Status: reviewed", but then the status was changed', null),
        );
    }

    /**
     * @dataProvider getCommentsForStatusChange
     */
    public function testOnIssueCommentAuthorSelfReview()
    {
        $this->statusApi->expects($this->never())
            ->method('setIssueStatus')
        ;
        
        $user = array('login' => 'weaverryan');

        $event = new GitHubEvent(array(
            'issue' => array(
                'number' => 1234,
                'user' => $user,
            ),
            'comment' => array(
                'body' => 'Status: reviewed',
                'user' => $user,
            ),
        ), $this->repository);

        self::$dispatcher->dispatch(GitHubEvents::ISSUE_COMMENT, $event);

        $responseData = $event->getResponseData();

        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['issue']);
        $this->assertNull($responseData['status_change']);
    }
}
