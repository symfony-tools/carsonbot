<?php

namespace App\Tests\Subscriber;

use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Model\Repository;
use App\Service\LabelNameExtractor;
use App\Subscriber\AutoUpdateTitleWithLabelSubscriber;
use App\Api\Label\FakedCachedLabelApi;
use Github\Api\Issue\Labels;
use Github\Api\PullRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\NullAdapter;
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
        $backendApi = $this->getMockBuilder(Labels::class)
            ->disableOriginalConstructor()
            ->getMock();
        $labelsApi = new FakedCachedLabelApi($backendApi, new NullAdapter());
        $this->pullRequestApi = $this->getMockBuilder(PullRequest::class)
            ->disableOriginalConstructor()
            ->setMethods(['update'])
            ->getMock();

        $this->subscriber = new AutoUpdateTitleWithLabelSubscriber($labelsApi, new LabelNameExtractor($labelsApi), $this->pullRequestApi);
        $this->repository = new Repository('carsonbot-playground', 'symfony', null);

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber($this->subscriber);
    }

    public function testOnPullRequestOpen()
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
}
