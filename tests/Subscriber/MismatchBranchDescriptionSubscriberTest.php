<?php

namespace App\Tests\Subscriber;

use App\Api\Issue\IssueApi;
use App\Api\Issue\NullIssueApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Model\Repository;
use App\Subscriber\MismatchBranchDescriptionSubscriber;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class MismatchBranchDescriptionSubscriberTest extends TestCase
{
    /**
     * @var IssueApi
     */
    private $issueApi;

    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    protected function setUp(): void
    {
        $this->issueApi = $this->createMock(NullIssueApi::class);

        $subscriber = new MismatchBranchDescriptionSubscriber($this->issueApi, new NullLogger());
        $this->repository = new Repository('carsonbot-playground', 'symfony', null);

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber($subscriber);
    }

    public function testOnPullRequestOpenMatch()
    {
        $this->issueApi->expects($this->never())
            ->method('commentOnIssue');

        $body = <<<TXT
| Q             | A
| ------------- | ---
| Branch?       | 6.2
| Bug fix?      | yes/no
TXT;

        $event = new GitHubEvent([
            'action' => 'opened',
            'pull_request' => [
                'number' => 1234,
                'body' => $body,
                'base' => [
                    'ref' => '6.2',
                ],
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();

        $this->assertCount(0, $responseData);
    }

    public function testOnPullRequestOpenMatchWithComment()
    {
        $this->issueApi->expects($this->never())
            ->method('commentOnIssue');

        $body = <<<TXT
| Q             | A
| ------------- | ---
| Branch?       | 6.2 <!-- see below -->
| Bug fix?      | yes/no
TXT;

        $event = new GitHubEvent([
            'action' => 'opened',
            'pull_request' => [
                'number' => 1234,
                'body' => $body,
                'base' => [
                    'ref' => '6.2',
                ],
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();

        $this->assertCount(0, $responseData);
    }

    public function testOnPullRequestOpenNotMatch()
    {
        $this->issueApi->expects($this->once())
            ->method('commentOnIssue');

        $body = <<<TXT
| Q             | A
| ------------- | ---
| Branch?       | 6.1
| Bug fix?      | yes/no
TXT;

        $event = new GitHubEvent([
            'action' => 'opened',
            'pull_request' => [
                'number' => 1234,
                'body' => $body,
                'base' => [
                    'ref' => '6.2',
                ],
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();

        $this->assertCount(1, $responseData);
        $this->assertSame(1234, $responseData['pull_request']);
    }

    public function testOnPullRequestOpenWithoutBranchRow()
    {
        $this->issueApi->expects($this->never())
            ->method('commentOnIssue');

        $body = <<<TXT
| Q             | A
| ------------- | ---
| Bug fix?      | yes/no
TXT;

        $event = new GitHubEvent([
            'action' => 'opened',
            'pull_request' => [
                'number' => 1234,
                'body' => $body,
                'base' => [
                    'ref' => '6.2',
                ],
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();

        $this->assertCount(0, $responseData);
    }

    public function testOnPullRequestOpenBadBranchFormat()
    {
        $this->issueApi->expects($this->once())
            ->method('commentOnIssue');

        $body = <<<TXT
| Q             | A
| ------------- | ---
| Branch?       | 6.2 for features / 4.4, 5.4, 6.0 or 6.1 for bug fixes <!-- see below -->
| Bug fix?      | yes/no
TXT;

        $event = new GitHubEvent([
            'action' => 'opened',
            'pull_request' => [
                'number' => 1234,
                'body' => $body,
                'base' => [
                    'ref' => '6.2',
                ],
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();

        $this->assertCount(1, $responseData);
        $this->assertSame(1234, $responseData['pull_request']);
    }

    public function testOnPullRequestOpenBranchNotInTable()
    {
        $this->issueApi->expects($this->never())
            ->method('commentOnIssue');

        $body = <<<TXT
Branch? 6.2
TXT;

        $event = new GitHubEvent([
            'action' => 'opened',
            'pull_request' => [
                'number' => 1234,
                'body' => $body,
                'base' => [
                    'ref' => '6.2',
                ],
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();

        $this->assertCount(0, $responseData);
    }

    public function testOnPullRequestEditedCleanup()
    {
        $this->issueApi->expects($this->once())
            ->method('findBotComment')
            ->with($this->repository, 1234, 'seems your PR description refers to branch')
            ->willReturn(777);

        $this->issueApi->expects($this->once())
            ->method('removeComment')
            ->with($this->repository, 777);

        $body = <<<TXT
| Q | A
| --- | ---
| Branch? | 6.2 |
TXT;

        $event = new GitHubEvent([
            'action' => 'edited',
            'pull_request' => [
                'number' => 1234,
                'body' => $body,
                'base' => ['ref' => '6.2'],
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
    }

    public function testOnPullRequestEditedIdempotency()
    {
        $this->issueApi->expects($this->once())
            ->method('findBotComment')
            ->with($this->repository, 1234, 'seems your PR description refers to branch')
            ->willReturn(777);

        $this->issueApi->expects($this->never())
            ->method('commentOnIssue');

        $body = <<<TXT
| Q | A
| --- | ---
| Branch? | 6.2 |
TXT;

        $event = new GitHubEvent([
            'action' => 'edited',
            'pull_request' => [
                'number' => 1234,
                'body' => $body,
                'base' => ['ref' => '5.4'], // Mismatch
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
    }
}
