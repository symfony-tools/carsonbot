<?php

namespace App\Tests\Subscriber;

use App\Api\Issue\IssueApi;
use App\Api\Issue\NullIssueApi;
use App\Api\PullRequest\NullPullRequestApi;
use App\Api\PullRequest\PullRequestApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Model\Repository;
use App\Subscriber\MissingChangelogSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class MissingChangelogSubscriberTest extends TestCase
{
    private IssueApi $issueApi;
    private PullRequestApi $pullRequestApi;
    private Repository $repository;
    private EventDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->issueApi = $this->createMock(NullIssueApi::class);
        $this->pullRequestApi = $this->createMock(NullPullRequestApi::class);

        $subscriber = new MissingChangelogSubscriber($this->issueApi, $this->pullRequestApi);
        $this->repository = new Repository('carsonbot-playground', 'symfony', null);

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber($subscriber);
    }

    public function testFeatureWithoutChangelogTriggersComment(): void
    {
        $this->pullRequestApi->expects($this->once())
            ->method('getFiles')
            ->willReturn(['src/Component/Console/Command.php', 'tests/ConsoleTest.php']);

        $this->issueApi->expects($this->once())
            ->method('commentOnIssue');

        $body = <<<TXT
| Q             | A
| ------------- | ---
| Branch?       | master
| Bug fix?      | no
| New feature?  | yes
| Deprecations? | no
TXT;

        $event = new GitHubEvent([
            'action' => 'opened',
            'pull_request' => [
                'number' => 1234,
                'body' => $body,
                'draft' => false,
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);

        $responseData = $event->getResponseData();
        $this->assertSame(1234, $responseData['pull_request']);
        $this->assertTrue($responseData['missing_changelog']);
    }

    public function testFeatureWithChangelogNoComment(): void
    {
        $this->pullRequestApi->expects($this->once())
            ->method('getFiles')
            ->willReturn(['src/Component/Console/Command.php', 'tests/ConsoleTest.php', 'src/Component/Console/CHANGELOG.md']);

        $this->issueApi->expects($this->never())
            ->method('commentOnIssue');

        $body = <<<TXT
| Q             | A
| ------------- | ---
| Branch?       | master
| Bug fix?      | no
| New feature?  | yes
| Deprecations? | no
TXT;

        $event = new GitHubEvent([
            'action' => 'opened',
            'pull_request' => [
                'number' => 1234,
                'body' => $body,
                'draft' => false,
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);

        $this->assertCount(0, $event->getResponseData());
    }

    public function testBugFixNoComment(): void
    {
        $this->pullRequestApi->expects($this->never())
            ->method('getFiles');

        $this->issueApi->expects($this->never())
            ->method('commentOnIssue');

        $body = <<<TXT
| Q             | A
| ------------- | ---
| Branch?       | master
| Bug fix?      | yes
| New feature?  | no
| Deprecations? | no
TXT;

        $event = new GitHubEvent([
            'action' => 'opened',
            'pull_request' => [
                'number' => 1234,
                'body' => $body,
                'draft' => false,
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);

        $this->assertCount(0, $event->getResponseData());
    }

    public function testDraftPRNoComment(): void
    {
        $this->pullRequestApi->expects($this->never())
            ->method('getFiles');

        $this->issueApi->expects($this->never())
            ->method('commentOnIssue');

        $body = <<<TXT
| Q             | A
| ------------- | ---
| New feature?  | yes
TXT;

        $event = new GitHubEvent([
            'action' => 'opened',
            'pull_request' => [
                'number' => 1234,
                'body' => $body,
                'draft' => true,
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);

        $this->assertCount(0, $event->getResponseData());
    }

    public function testOtherActionNoComment(): void
    {
        $this->pullRequestApi->expects($this->never())
            ->method('getFiles');

        $this->issueApi->expects($this->never())
            ->method('commentOnIssue');

        $body = <<<TXT
| Q             | A
| ------------- | ---
| New feature?  | yes
TXT;

        $event = new GitHubEvent([
            'action' => 'edited',
            'pull_request' => [
                'number' => 1234,
                'body' => $body,
                'draft' => false,
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);

        $this->assertCount(0, $event->getResponseData());
    }
}
