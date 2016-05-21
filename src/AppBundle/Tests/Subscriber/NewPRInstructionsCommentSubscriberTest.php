<?php

namespace AppBundle\Tests\Subscriber;

use AppBundle\Event\GitHubEvent;
use AppBundle\GitHubEvents;
use AppBundle\Repository\Repository;
use AppBundle\Subscriber\NewPRInstructionCommentSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcher;

class NewPRInstructionsCommentSubscriberTest extends \PHPUnit_Framework_TestCase
{
    private $newPRSubscriber;

    private $commentsApi;

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
        $this->commentsApi = $this->getMock('AppBundle\Issues\CommentsApiInterface');
        $this->newPRSubscriber = new NewPRInstructionCommentSubscriber($this->commentsApi);
        $this->repository = new Repository('weaverryan', 'symfony', [], null);

        self::$dispatcher->addSubscriber($this->newPRSubscriber);
    }

    public function testOnPullRequestOpen()
    {
        $this->commentsApi->expects($this->once())
            ->method('commentOnIssue');

        $event = new GitHubEvent(array(
            'action' => 'opened',
            'pull_request' => array('number' => 1234),
        ), $this->repository);

        self::$dispatcher->dispatch(GitHubEvents::PULL_REQUEST, $event);

        $responseData = $event->getResponseData();

        $this->assertCount(1, $responseData);
        $this->assertSame(true, $responseData['instructions_added']);
    }

    public function testOnPullRequestNotOpen()
    {
        $this->commentsApi->expects($this->never())
            ->method('commentOnIssue');

        $event = new GitHubEvent(array(
            'action' => 'close',
        ), $this->repository);

        self::$dispatcher->dispatch(GitHubEvents::PULL_REQUEST, $event);

        $responseData = $event->getResponseData();

        $this->assertCount(1, $responseData);
        $this->assertSame('close', $responseData['unsupported_action']);
    }
}
