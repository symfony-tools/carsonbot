<?php

namespace AppBundle\Tests\Subscriber;

use AppBundle\Event\GitHubEvent;
use AppBundle\GitHubEvents;
use AppBundle\Issues\Status;
use AppBundle\Repository\Repository;
use AppBundle\Subscriber\NeedsReviewNewPRSubscriber;
use AppBundle\Subscriber\StatusChangeByCommentSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcher;

class NeedsReviewNewPRSubscriberTest extends \PHPUnit_Framework_TestCase
{
    private $needsReviewSubscriber;

    private $statusApi;

    private $repository;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    protected function setUp()
    {
        $this->statusApi = $this->getMock('AppBundle\Issues\StatusApi');
        $this->needsReviewSubscriber = new NeedsReviewNewPRSubscriber($this->statusApi);
        $this->repository = new Repository('weaverryan', 'symfony', [], null);

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber($this->needsReviewSubscriber);
    }

    /**
     * @dataProvider getPRTests
     */
    public function testOnPullRequestOpen($prTitle, $expectedStatus)
    {
        $this->statusApi->expects($this->once())
            ->method('setIssueStatus')
            ->with(1234, $expectedStatus);

        $event = new GitHubEvent(array(
            'action' => 'opened',
            'pull_request' => array('number' => 1234, 'title' => $prTitle),
        ), $this->repository);

        $this->dispatcher->dispatch(GitHubEvents::PULL_REQUEST, $event);

        $responseData = $event->getResponseData();

        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['pull_request']);
        $this->assertSame($expectedStatus, $responseData['status_change']);
    }

    public function getPRTests()
    {
        $tests = [];

        $tests[] = ['Cool new PR!', Status::NEEDS_REVIEW];
        $tests[] = ['[WIP] Still working', Status::NEEDS_WORK];
        $tests[] = ['[wip] Still working', Status::NEEDS_WORK];
        $tests[] = ['[Security][WIP] Still working', Status::NEEDS_WORK];
        // require it to be inside the [WIP]
        $tests[] = ['WIP Still working', Status::NEEDS_REVIEW];

        return $tests;
    }

    public function testOnPullRequestNotOpen()
    {
        $this->statusApi->expects($this->never())
            ->method('setIssueStatus');

        $event = new GitHubEvent(array(
            'action' => 'close',
        ), $this->repository);

        $this->dispatcher->dispatch(GitHubEvents::PULL_REQUEST, $event);

        $responseData = $event->getResponseData();

        $this->assertCount(1, $responseData);
        $this->assertSame('close', $responseData['unsupported_action']);
    }
}
