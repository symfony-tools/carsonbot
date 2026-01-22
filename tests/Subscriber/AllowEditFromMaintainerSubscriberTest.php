<?php

namespace App\Tests\Subscriber;

use App\Api\Issue\NullIssueApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Model\Repository;
use App\Subscriber\AllowEditFromMaintainerSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class AllowEditFromMaintainerSubscriberTest extends TestCase
{
    private $issueApi;
    private $repository;
    private $dispatcher;

    protected function setUp(): void
    {
        $this->issueApi = $this->createMock(NullIssueApi::class);
        $this->repository = new Repository('carsonbot-playground', 'symfony', null);

        $subscriber = new AllowEditFromMaintainerSubscriber($this->issueApi);

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber($subscriber);
    }

    public function testOnPullRequestEditedCleanup()
    {
        $this->issueApi->expects($this->once())
            ->method('findBotComment')
            ->with($this->repository, 1234, 'Allow edits from maintainer')
            ->willReturn(666);

        $this->issueApi->expects($this->once())
            ->method('removeComment')
            ->with($this->repository, 666);

        $event = new GitHubEvent([
            'action' => 'edited',
            'pull_request' => [
                'number' => 1234,
                'maintainer_can_modify' => true,
                'head' => ['repo' => ['full_name' => 'contributor/symfony']],
            ],
            'repository' => ['full_name' => 'fabpot/symfony', 'owner' => ['login' => 'fabpot'], 'name' => 'symfony'],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
    }

    public function testOnPullRequestEditedIdempotency()
    {
        $this->issueApi->expects($this->once())
            ->method('findBotComment')
            ->with($this->repository, 1234, 'Allow edits from maintainer')
            ->willReturn(666);

        $this->issueApi->expects($this->never())
            ->method('commentOnIssue');

        $event = new GitHubEvent([
            'action' => 'edited',
            'pull_request' => [
                'number' => 1234,
                'maintainer_can_modify' => false,
                'head' => ['repo' => ['full_name' => 'contributor/symfony']],
            ],
            'repository' => ['full_name' => 'fabpot/symfony', 'owner' => ['login' => 'fabpot'], 'name' => 'symfony'],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
    }
}
