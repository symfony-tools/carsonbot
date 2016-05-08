<?php

namespace AppBundle\Tests\Issues;

use AppBundle\Event\GitHubEvent;
use AppBundle\GitHubEvents;
use AppBundle\Issues\IssueListener;
use AppBundle\Issues\Status;
use AppBundle\Issues\StatusApi;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class IssueListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var StatusApi|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $statusApi;

    /**
     * @var IssueListener
     */
    private $listener;

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
        $this->listener = $this->getListener($this->statusApi);
        self::$dispatcher->addSubscriber($this->listener);
    }

    protected function tearDown()
    {
        self::$dispatcher->removeSubscriber($this->listener);
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
            'issue' => array('number' => 1234),
            'comment' => array('body' => $comment),
        ));

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

    public function testOnPullRequestOpen()
    {
        $this->statusApi->expects($this->once())
            ->method('setIssueStatus')
            ->with(1234, Status::NEEDS_REVIEW);

        $event = new GitHubEvent(array(
            'action' => 'open',
            'pull_request' => array('number' => 1234),
        ));

        self::$dispatcher->dispatch(GitHubEvents::PULL_REQUEST, $event);

        $responseData = $event->getResponseData();

        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['pull_request']);
        $this->assertSame(Status::NEEDS_REVIEW, $responseData['status_change']);
    }

    public function testOnPullRequestNotOpen()
    {
        $this->statusApi->expects($this->never())
            ->method('setIssueStatus');

        $event = new GitHubEvent(array(
            'action' => 'close',
        ));

        self::$dispatcher->dispatch(GitHubEvents::PULL_REQUEST, $event);

        $responseData = $event->getResponseData();

        $this->assertCount(1, $responseData);
        $this->assertSame('close', $responseData['unsupported_action']);
    }

    public function testOnIssuesLabeledBug()
    {
        $this->statusApi->expects($this->once())
            ->method('getIssueStatus')
            ->with(1234)
            ->willReturn(null);

        $this->statusApi->expects($this->once())
            ->method('setIssueStatus')
            ->with(1234, Status::NEEDS_REVIEW);

        $event = new GitHubEvent(array(
            'action' => 'labeled',
            'issue' => array('number' => 1234),
            'label' => array('name' => 'bug'),
        ));

        self::$dispatcher->dispatch(GitHubEvents::ISSUES, $event);

        $responseData = $event->getResponseData();

        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['issue']);
        $this->assertSame(Status::NEEDS_REVIEW, $responseData['status_change']);
    }

    public function testOnIssuesLabeledBugIgnoresCase()
    {
        $this->statusApi->expects($this->once())
            ->method('getIssueStatus')
            ->with(1234)
            ->willReturn(null);

        $this->statusApi->expects($this->once())
            ->method('setIssueStatus')
            ->with(1234, Status::NEEDS_REVIEW);

        $event = new GitHubEvent(array(
            'action' => 'labeled',
            'issue' => array('number' => 1234),
            'label' => array('name' => 'BUG'),
        ));

        self::$dispatcher->dispatch(GitHubEvents::ISSUES, $event);

        $responseData = $event->getResponseData();

        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['issue']);
        $this->assertSame(Status::NEEDS_REVIEW, $responseData['status_change']);
    }

    public function testOnIssuesIgnoresNonBugs()
    {
        $this->statusApi->expects($this->never())
            ->method('getIssueStatus');

        $this->statusApi->expects($this->never())
            ->method('setIssueStatus');

        $event = new GitHubEvent(array(
            'action' => 'labeled',
            'issue' => array('number' => 1234),
            'label' => array('name' => 'feature'),
        ));

        self::$dispatcher->dispatch(GitHubEvents::ISSUES, $event);

        $responseData = $event->getResponseData();

        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['issue']);
        $this->assertNull($responseData['status_change']);
    }

    public function testOnIssuesIgnoresIfExistingStatus()
    {
        $this->statusApi->expects($this->once())
            ->method('getIssueStatus')
            ->with(1234)
            ->willReturn(Status::NEEDS_WORK);

        $this->statusApi->expects($this->never())
            ->method('setIssueStatus');

        $event = new GitHubEvent(array(
            'action' => 'labeled',
            'issue' => array('number' => 1234),
            'label' => array('name' => 'bug'),
        ));

        self::$dispatcher->dispatch(GitHubEvents::ISSUES, $event);

        $responseData = $event->getResponseData();

        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['issue']);
        $this->assertNull($responseData['status_change']);
    }

    public function testOnIssuesIgnoresNonLabeled()
    {
        $this->statusApi->expects($this->never())
            ->method('getIssueStatus');

        $this->statusApi->expects($this->never())
            ->method('setIssueStatus');

        $event = new GitHubEvent(array(
            'action' => 'close',
            'issue' => array('number' => 1234),
            'label' => array('name' => 'bug'),
        ));

        self::$dispatcher->dispatch(GitHubEvents::ISSUES, $event);

        $responseData = $event->getResponseData();

        $this->assertCount(1, $responseData);
        $this->assertSame('close', $responseData['unsupported_action']);
    }

    abstract protected function getListener($statusApi);
}
