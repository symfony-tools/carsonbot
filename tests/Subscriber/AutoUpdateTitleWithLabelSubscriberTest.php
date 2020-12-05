<?php

namespace App\Tests\Subscriber;

use App\Api\Label\StaticLabelApi;
use App\Api\PullRequest\NullPullRequestApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Model\Repository;
use App\Service\LabelNameExtractor;
use App\Subscriber\AutoUpdateTitleWithLabelSubscriber;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\PersistingStoreInterface;

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
        $this->pullRequestApi = $this->getMockBuilder(NullPullRequestApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['show'])
            ->getMock();

        $store = $this->getMockBuilder(PersistingStoreInterface::class)->getMock();
        $store->method('exists')->willReturn(false);

        $this->subscriber = new AutoUpdateTitleWithLabelSubscriber($labelsApi, new LabelNameExtractor($labelsApi, new NullLogger()), $this->pullRequestApi, new LockFactory($store));
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

    public function testOnPullRequestLabeledCaseInsensitive()
    {
        $event = new GitHubEvent([
            'action' => 'labeled',
            'number' => 1234,
            'pull_request' => [
                'title' => '[PHPunitbridge] Foo',
                'labels' => [
                    ['name' => 'PhpUnitBridge', 'color' => 'dddddd'],
                ],
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();

        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['pull_request']);
        $this->assertSame('[PhpUnitBridge] Foo', $responseData['new_title']);
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

    /**
     * If a user add two labels at the same time. We will get two webhooks simultaneously.
     * We need to make sure that when we request the Github API it will return the changes
     * from the first webhook.
     */
    public function testOnPullRequestLabeledTwice()
    {
        $this->pullRequestApi->method('show')->willReturn(['title' => '[Console][FrameworkBundle] Foo normal title']);
        $event = new GitHubEvent([
            'action' => 'labeled',
            'number' => 1234,
            'pull_request' => [
                'title' => 'Foo normal title',
                'labels' => [
                    ['name' => 'FrameworkBundle', 'color' => 'dddddd'],
                    ['name' => 'Console', 'color' => 'dddddd'],
                ],
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();
        $this->assertEmpty($responseData);
    }
}
