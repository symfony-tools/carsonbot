<?php

namespace App\Tests\Subscriber;

use App\Api\Label\StaticLabelApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Model\Repository;
use App\Service\LabelNameExtractor;
use App\Subscriber\AutoUpdateTitleWithLabelSubscriber;
use Github\Api\PullRequest;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;

class AutoUpdateTitleWithLabelSubscriberTest extends TestCase
{
    /**
     * @var EventDispatcher
     */
    private $dispatcher;
    private $pullRequestApi;
    private $subscriber;
    private $repository;

    protected function setUp()
    {
        $labelsApi = new StaticLabelApi();
        $this->pullRequestApi = $this->getMockBuilder(PullRequest::class)
            ->disableOriginalConstructor()
            ->setMethods(['update'])
            ->getMock();

        $this->subscriber = new AutoUpdateTitleWithLabelSubscriber($labelsApi, new LabelNameExtractor($labelsApi, new NullLogger()), $this->pullRequestApi);
        $this->repository = new Repository('carsonbot-playground', 'symfony', null);

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber($this->subscriber);
    }

    public function testOnPullRequestLabeled()
    {
        $event = new GitHubEvent([
            'action' => 'labeled',
            'number' => 1234,
            'pull_request' => [
                'title' => '[fwb][bar] Foo',
                'labels' => [
                    ['name' => 'FrameworkBundle', 'color' => 'dddddd'],
                    ['name' => 'Console', 'color' => 'dddddd'],
                ],
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();

        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['pull_request']);
        $this->assertSame('[Console][FrameworkBundle] [bar] Foo', $responseData['new_title']);
    }

    public function testOnPullRequestLabeledWithExisting()
    {
        $event = new GitHubEvent([
            'action' => 'labeled',
            'number' => 1234,
            'pull_request' => [
                'title' => '[Messenger] Fix JSON',
                'labels' => [
                    ['name' => 'Status: Needs Review', 'color' => 'abcabc'],
                    ['name' => 'Messenger', 'color' => 'dddddd'],
                ],
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();
        $this->assertEmpty($responseData);
    }
}
