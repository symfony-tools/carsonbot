<?php

declare(strict_types=1);

namespace Subscriber;

use App\Api\Label\NullLabelApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Model\Repository;
use App\Subscriber\RemoveStalledLabelOnCommentSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class RemoveStalledLabelOnCommentSubscriberTest extends TestCase
{
    private $subscriber;

    private $repository;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    protected function setUp(): void
    {
        $this->subscriber = new RemoveStalledLabelOnCommentSubscriber(new NullLabelApi(), 'carsonbot');
        $this->repository = new Repository('carsonbot-playground', 'symfony', null);

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber($this->subscriber);
    }

    public function testOnComment()
    {
        $event = new GitHubEvent([
            'issue' => ['number' => 1234, 'state' => 'open', 'labels' => []], 'comment' => ['user' => ['login' => 'nyholm']],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::ISSUE_COMMENT);

        $responseData = $event->getResponseData();
        $this->assertEmpty($responseData);
    }

    public function testOnCommentOnStale()
    {
        $event = new GitHubEvent([
            'issue' => ['number' => 1234, 'state' => 'open', 'labels' => [['name' => 'Foo'], ['name' => 'Stalled']]], 'comment' => ['user' => ['login' => 'nyholm']],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::ISSUE_COMMENT);

        $responseData = $event->getResponseData();
        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['issue']);
        $this->assertSame(true, $responseData['removed_stalled_label']);
    }

    public function testOnBotCommentOnStale()
    {
        $event = new GitHubEvent([
            'issue' => ['number' => 1234, 'state' => 'open', 'labels' => [['name' => 'Foo'], ['name' => 'Stalled']]], 'comment' => ['user' => ['login' => 'carsonbot']],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::ISSUE_COMMENT);

        $responseData = $event->getResponseData();
        $this->assertEmpty($responseData);
    }

    public function testCommentOnClosed()
    {
        $event = new GitHubEvent([
            'issue' => ['number' => 1234, 'state' => 'closed', 'labels' => [['name' => 'Foo'], ['name' => 'Stalled']]], 'comment' => ['user' => ['login' => 'nyholm']],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::ISSUE_COMMENT);

        $responseData = $event->getResponseData();
        $this->assertEmpty($responseData);
    }
}
