<?php

namespace AppBundle\Tests\Subscriber;

use AppBundle\Event\GitHubEvent;
use AppBundle\GitHubEvents;
use AppBundle\Issues\Status;
use AppBundle\Repository\Repository;
use AppBundle\Subscriber\StatusChangeOnPushSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcher;

class StatusChangeOnPushSubscriberTest extends \PHPUnit_Framework_TestCase
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
        $this->statusChangeSubscriber = new StatusChangeOnPushSubscriber($this->statusApi);
        $this->repository = new Repository('weaverryan', 'symfony', [], null);

        self::$dispatcher->addSubscriber($this->statusChangeSubscriber);
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

        self::$dispatcher->dispatch(GitHubEvents::PULL_REQUEST, $event);

        $responseData = $event->getResponseData();

        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['pull_request']);
        $this->assertSame($statusChange, $responseData['status_change']);
    }

    public function getStatuses()
    {
        return array(
            array(Status::NEEDS_WORK, Status::NEEDS_REVIEW),
            array(Status::REVIEWED, null),
            array(Status::WORKS_FOR_ME, null),
            array(Status::NEEDS_REVIEW, null),
        );
    }

    public function testOnNonPushPullRequestEvent()
    {
        $this->statusApi->expects($this->any())
            ->method('getIssueStatus')
            ->with(1234, $this->repository)
            ->will($this->returnValue(Status::NEEDS_WORK));

        $event = new GitHubEvent(array(
            'action' => 'labeled',
            'pull_request' => $this->getPullRequestData(),
        ), $this->repository);

        self::$dispatcher->dispatch(GitHubEvents::PULL_REQUEST, $event);

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

        $event = new GitHubEvent(array(
            'action' => 'synchronize',
            'pull_request' => $this->getPullRequestData('[wip] needs some more work.'),
        ), $this->repository);

        self::$dispatcher->dispatch(GitHubEvents::PULL_REQUEST, $event);

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
