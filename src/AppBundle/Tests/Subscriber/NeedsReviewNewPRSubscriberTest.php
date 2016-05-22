<?php

namespace AppBundle\Tests\Subscriber;

use AppBundle\Event\GitHubEvent;
use AppBundle\GitHubEvents;
use AppBundle\Issues\Status;
use AppBundle\Repository\Repository;
use AppBundle\Subscriber\NeedsReviewNewPRSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcher;

class NeedsReviewNewPRSubscriberTest extends \PHPUnit_Framework_TestCase
{
    private $needsReviewSubscriber;

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
        $this->needsReviewSubscriber = new NeedsReviewNewPRSubscriber($this->statusApi);
        $this->repository = new Repository('weaverryan', 'symfony', [], null);

        self::$dispatcher->addSubscriber($this->needsReviewSubscriber);
    }

    public function testOnPullRequestOpen()
    {
        $this->statusApi->expects($this->once())
            ->method('setIssueStatus')
            ->with(1234, Status::NEEDS_REVIEW);

        $event = new GitHubEvent(array(
            'action' => 'opened',
            'pull_request' => array('number' => 1234),
        ), $this->repository);

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
        ), $this->repository);

        self::$dispatcher->dispatch(GitHubEvents::PULL_REQUEST, $event);

        $responseData = $event->getResponseData();

        $this->assertCount(1, $responseData);
        $this->assertSame('close', $responseData['unsupported_action']);
    }
}
