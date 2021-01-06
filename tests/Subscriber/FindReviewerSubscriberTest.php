<?php

namespace App\Tests\Subscriber;

use App\Api\PullRequest\NullPullRequestApi;
use App\Command\SuggestReviewerCommand;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Model\Repository;
use App\Service\TaskScheduler;
use App\Subscriber\FindReviewerSubscriber;
use App\Tests\ValidCommandProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class FindReviewerSubscriberTest extends TestCase
{
    private $subscriber;

    private $pullRequestApi;
    private $taskScheduler;

    private $repository;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    protected function setUp()
    {
        $this->pullRequestApi = $this->createMock(NullPullRequestApi::class);
        $this->taskScheduler = $this->createMock(TaskScheduler::class);

        $this->subscriber = new FindReviewerSubscriber($this->pullRequestApi, $this->taskScheduler, 'carsonbot');
        $this->repository = new Repository('carsonbot-playground', 'symfony', null);

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber($this->subscriber);
    }

    /**
     * @dataProvider commentProvider
     */
    public function testOnComment(string $body, bool $valid)
    {
        if ($valid) {
            $this->pullRequestApi->expects($this->once())
                ->method('findReviewer')
                ->with($this->repository, 4711, SuggestReviewerCommand::TYPE_DEMAND);
        } else {
            $this->pullRequestApi->expects($this->never())->method('findReviewer');
        }

        $event = new GitHubEvent([
            'action' => 'created',
            'issue' => [
                'number' => 4711,
            ],
            'comment' => [
                'body' => $body,
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::ISSUE_COMMENT);
        $responseData = $event->getResponseData();

        if ($valid) {
            $this->assertCount(2, $responseData);
            $this->assertSame(4711, $responseData['issue']);
            $this->assertSame(true, $responseData['suggest-review']);
        } else {
            $this->assertEmpty($responseData);
        }
    }

    public function commentProvider()
    {
        yield ['@carsonbot reviewer', true];
        yield ['@carsonbot review', true];
        yield ['@carsonbot find a reviewer', true];
        yield ['@carsonbot review please', true];
        yield ['Please @carsonbot, fetch a reviewer', true];
        yield ['Some random', false];
        yield ['@carsonbot foobar', false];
        yield ['See the review please', false];
        yield ["@carsonbot please\nfind a reviewer for me", false];

        foreach (ValidCommandProvider::get() as $data) {
            yield [$data[0], FindReviewerSubscriber::class === $data[1]];
        }
    }
}
