<?php

namespace App\Tests\Subscriber;

use App\Api\Issue\IssueApi;
use App\Api\Issue\NullIssueApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Model\Repository;
use App\Service\ComplementGenerator;
use App\Service\SymfonyVersionProvider;
use App\Subscriber\UnsupportedBranchSubscriber;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;

class UnsupportedBranchSubscriberTest extends TestCase
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

    protected function setUp()
    {
        $this->issueApi = $this->createMock(NullIssueApi::class);
        $symfonyVersionProvider = $this->getMockBuilder(SymfonyVersionProvider::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSupportedVersions'])
            ->getMock();
        $symfonyVersionProvider->method('getSupportedVersions')->willReturn(['4.4', '5.1']);

        $subscriber = new UnsupportedBranchSubscriber($symfonyVersionProvider, $this->issueApi, new ComplementGenerator(), new NullLogger());
        $this->repository = new Repository('carsonbot-playground', 'symfony', null);

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber($subscriber);
    }

    public function testOnPullRequestOpen()
    {
        $this->issueApi->expects($this->once())
            ->method('commentOnIssue');

        $event = new GitHubEvent([
            'action' => 'opened',
            'pull_request' => [
                'number' => 1234,
                'base' => ['ref' => '4.3'],
            ],
            'repository' => [
                'default_branch' => '5.x',
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();

        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['pull_request']);
        $this->assertSame('4.3', $responseData['unsupported_branch']);
    }

    public function testOnPullRequestOpenDefaultBranch()
    {
        $this->issueApi->expects($this->never())
            ->method('commentOnIssue');

        $event = new GitHubEvent([
            'action' => 'opened',
            'pull_request' => [
                'number' => 1234,
                'base' => ['ref' => '4.4'],
            ],
            'repository' => [
                'default_branch' => '5.x',
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();
        $this->assertEmpty($responseData);
    }

    public function testOnPullRequestNotOpen()
    {
        $this->issueApi->expects($this->never())
            ->method('commentOnIssue');

        $event = new GitHubEvent([
            'action' => 'close',
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();
        $this->assertEmpty($responseData);
    }
}
