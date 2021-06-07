<?php

namespace App\Tests\Subscriber;

use App\Api\PullRequest\NullPullRequestApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Model\Repository;
use App\Subscriber\RewriteUnwantedPhrasesSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\PersistingStoreInterface;

class RewriteUnwantedPhrasesSubscriberTest extends TestCase
{
    /**
     * @var EventDispatcher
     */
    private $dispatcher;
    private $pullRequestApi;
    private $subscriber;
    private $repository;

    protected function setUp(): void
    {
        $this->pullRequestApi = $this->getMockBuilder(NullPullRequestApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['show', 'updateTitle'])
            ->getMock();

        $store = $this->getMockBuilder(PersistingStoreInterface::class)->getMock();
        $store->method('exists')->willReturn(false);

        $this->subscriber = new RewriteUnwantedPhrasesSubscriber($this->pullRequestApi, new LockFactory($store));
        $this->repository = new Repository('carsonbot-playground', 'symfony', null);

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber($this->subscriber);
    }

    public function testReplaceUnwantedInTitle()
    {
        $event = new GitHubEvent(['action' => 'opened', 'number' => 1234, 'pull_request' => []], $this->repository);
        $this->pullRequestApi->method('show')->willReturn([
            'title' => 'Remove dead code',
            'body' => 'foobar',
        ]);

        $this->pullRequestApi->expects($this->once())->method('updateTitle')->with($this->repository, 1234, 'Remove unused code', 'foobar');

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();
        $this->assertCount(2, $responseData);
    }

    public function testReplaceUnwantedInBody()
    {
        $event = new GitHubEvent(['action' => 'opened', 'number' => 1234, 'pull_request' => []], $this->repository);
        $this->pullRequestApi->method('show')->willReturn([
            'title' => 'Foobar',
            'body' => 'I want to remove dead code.',
        ]);

        $this->pullRequestApi->expects($this->once())->method('updateTitle')->with($this->repository, 1234, 'Foobar', 'I want to remove unused code.');

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();
        $this->assertCount(2, $responseData);
    }

    public function testDoNothingOnAllowedContent()
    {
        $event = new GitHubEvent(['action' => 'opened', 'number' => 1234, 'pull_request' => []], $this->repository);
        $this->pullRequestApi->method('show')->willReturn([
            'title' => 'Hello world',
            'body' => 'foobar',
        ]);

        $this->pullRequestApi->expects($this->never())->method('updateTitle');

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();
        $this->assertCount(0, $responseData);
    }
}
