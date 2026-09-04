<?php

namespace App\Tests\Subscriber;

use App\Api\Issue\IssueApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Model\Repository;
use App\Subscriber\CongratulateFirstTimeMergedContributorSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class CongratulateFirstTimeMergedContributorSubscriberTest extends TestCase
{
    private IssueApi $issueApi;
    private Repository $repository;
    private EventDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->issueApi = $this->createMock(IssueApi::class);
        $this->repository = new Repository('symfony', 'symfony', null);

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber(new CongratulateFirstTimeMergedContributorSubscriber($this->issueApi));
    }

    public function testPostsCommentOnFirstTimeMergedContributor()
    {
        $this->issueApi->expects($this->once())
            ->method('commentOnIssue')
            ->with(
                $this->repository,
                1234,
                $this->stringContains("You're officially a Symfony contributor — welcome to the club!")
            );

        $this->dispatcher->dispatch(new GitHubEvent([
            'action' => 'closed',
            'merged' => true,
            'pull_request' => [
                'number' => 1234,
                'author_association' => 'FIRST_TIME_CONTRIBUTOR',
            ],
        ], $this->repository), GitHubEvents::PULL_REQUEST);
    }

    public function testSkipsNonMergedClosedPR()
    {
        $this->issueApi->expects($this->never())->method('commentOnIssue');

        $this->dispatcher->dispatch(new GitHubEvent([
            'action' => 'closed',
            'merged' => false,
            'pull_request' => [
                'number' => 1234,
                'author_association' => 'FIRST_TIME_CONTRIBUTOR',
            ],
        ], $this->repository), GitHubEvents::PULL_REQUEST);
    }

    public function testSkipsExistingContributor()
    {
        $this->issueApi->expects($this->never())->method('commentOnIssue');

        $this->dispatcher->dispatch(new GitHubEvent([
            'action' => 'closed',
            'merged' => true,
            'pull_request' => [
                'number' => 1234,
                'author_association' => 'CONTRIBUTOR',
            ],
        ], $this->repository), GitHubEvents::PULL_REQUEST);
    }

    public function testGoodFirstIssueLinkPointsToCorrectRepo()
    {
        $this->issueApi->expects($this->once())
            ->method('commentOnIssue')
            ->with(
                $this->repository,
                1234,
                $this->stringContains('https://github.com/symfony/symfony/issues?q=is%3Aissue%20state%3Aopen%20label%3A%22Good%20first%20issue%22')
            );

        $this->dispatcher->dispatch(new GitHubEvent([
            'action' => 'closed',
            'merged' => true,
            'pull_request' => [
                'number' => 1234,
                'author_association' => 'FIRST_TIMER',
            ],
        ], $this->repository), GitHubEvents::PULL_REQUEST);
    }
}
