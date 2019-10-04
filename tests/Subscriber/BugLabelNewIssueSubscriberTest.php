<?php

namespace App\Tests\Subscriber;

use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Issues\Status;
use App\Repository\Repository;
use App\Subscriber\BugLabelNewIssueSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class BugLabelNewIssueSubscriberTest extends TestCase
{
    private $bugLabelSubscriber;
    private $statusApi;
    private $repository;
    private $dispatcher;

    protected function setUp()
    {
        $this->statusApi = $this->getMockBuilder('App\Issues\StatusApi')->getMock();

        $this->bugLabelSubscriber = new BugLabelNewIssueSubscriber($this->statusApi);
        $this->repository = new Repository('weaverryan', 'symfony', [], null);

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber($this->bugLabelSubscriber);
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
        ), $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::ISSUES);

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
        ), $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::ISSUES);

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
        ), $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::ISSUES);

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
        ), $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::ISSUES);

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
        ), $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::ISSUES);

        $responseData = $event->getResponseData();

        $this->assertCount(1, $responseData);
        $this->assertSame('close', $responseData['unsupported_action']);
    }
}