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

    protected function setUp(): void
    {
        $labelsApi = new StaticLabelApi();
        $this->pullRequestApi = $this->getMockBuilder(NullPullRequestApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['show'])
            ->getMock();

        $store = $this->getMockBuilder(PersistingStoreInterface::class)->getMock();
        $store->method('exists')->willReturn(false);

        $this->subscriber = new AutoUpdateTitleWithLabelSubscriber(new LabelNameExtractor($labelsApi, new NullLogger()), $this->pullRequestApi, new LockFactory($store));
        $this->repository = new Repository('carsonbot-playground', 'symfony', null);

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber($this->subscriber);
    }

    public function testOnPullRequestLabeled()
    {
        $event = new GitHubEvent(['action' => 'labeled', 'number' => 1234, 'pull_request' => []], $this->repository);
        $this->pullRequestApi->method('show')->willReturn([
            'title' => '[fwb][bar] Foo',
            'labels' => [
                ['name' => 'FrameworkBundle', 'color' => 'dddddd'],
                ['name' => 'Console', 'color' => 'dddddd'],
            ],
        ]);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();

        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['pull_request']);
        $this->assertSame('[Console][FrameworkBundle] [bar] Foo', $responseData['new_title']);
    }

    public function testOnPullRequestLabeledCaseInsensitive()
    {
        $event = new GitHubEvent(['action' => 'labeled', 'number' => 1234, 'pull_request' => []], $this->repository);
        $this->pullRequestApi->method('show')->willReturn([
            'title' => '[PHPunitbridge] Foo',
            'labels' => [
                ['name' => 'PhpUnitBridge', 'color' => 'dddddd'],
            ],
        ]);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();

        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['pull_request']);
        $this->assertSame('[PhpUnitBridge] Foo', $responseData['new_title']);
    }

    public function testOnPullRequestLabeledWithExisting()
    {
        $event = new GitHubEvent(['action' => 'labeled', 'number' => 1234, 'pull_request' => []], $this->repository);
        $this->pullRequestApi->method('show')->willReturn([
                'title' => '[Messenger] Fix JSON',
                'labels' => [
                    ['name' => 'Status: Needs Review', 'color' => 'abcabc'],
                    ['name' => 'Messenger', 'color' => 'dddddd'],
                ],
            ]);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();
        $this->assertEmpty($responseData);
    }

    public function testRemoveLabel()
    {
        $event = new GitHubEvent(['action' => 'labeled', 'number' => 1234, 'pull_request' => []], $this->repository);
        $this->pullRequestApi->method('show')->willReturn([
                'title' => '[Console][FrameworkBundle] [Random] Foo normal title',
                'labels' => [
                    ['name' => 'Console', 'color' => 'dddddd'],
                ],
            ]);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();
        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['pull_request']);
        $this->assertSame('[Console] [Random] Foo normal title', $responseData['new_title']);
    }

    public function testExtraBlankSpace()
    {
        $event = new GitHubEvent(['action' => 'labeled', 'number' => 57753, 'pull_request' => []], $this->repository);
        $this->pullRequestApi->method('show')->willReturn([
                'title' => '[ErrorHandler]&nbsp;restrict the maximum length of the X-Debug-Exception header',
                'labels' => [
                    ['name' => 'ErrorHandler', 'color' => 'dddddd'],
                ],
            ]);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();
        $this->assertCount(2, $responseData);
        $this->assertSame(57753, $responseData['pull_request']);
        $this->assertSame('[ErrorHandler] restrict the maximum length of the X-Debug-Exception header', $responseData['new_title']);
    }
}
