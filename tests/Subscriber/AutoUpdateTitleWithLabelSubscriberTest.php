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
        $this->assertSame('[Console][FrameworkBundle][bar] Foo', $responseData['new_title']);
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
        $this->assertSame('[Console][Random] Foo normal title', $responseData['new_title']);
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

    public function testMultipleLabelsWithoutSpaceBetweenBrackets()
    {
        $event = new GitHubEvent(['action' => 'labeled', 'number' => 1234, 'pull_request' => []], $this->repository);
        $this->pullRequestApi->method('show')->willReturn([
            'title' => 'Foo Bar',
            'labels' => [
                ['name' => 'Platform', 'color' => 'dddddd'],
                ['name' => 'Agent', 'color' => 'dddddd'],
            ],
        ]);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();
        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['pull_request']);
        $this->assertSame('[Agent][Platform] Foo Bar', $responseData['new_title']);
    }

    public function testMultipleLabelsUpdateRemovesSpaceBetweenBrackets()
    {
        $event = new GitHubEvent(['action' => 'labeled', 'number' => 1234, 'pull_request' => []], $this->repository);
        $this->pullRequestApi->method('show')->willReturn([
            'title' => '[Platform] [Agent] Foo Bar',  // Title already has labels with space
            'labels' => [
                ['name' => 'Platform', 'color' => 'dddddd'],
                ['name' => 'Agent', 'color' => 'dddddd'],
            ],
        ]);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();
        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['pull_request']);
        $this->assertSame('[Agent][Platform] Foo Bar', $responseData['new_title']);
    }

    public function testAddingLabelToExistingLabeledTitle()
    {
        // Simulating when we already have [Platform] and are adding Agent label
        $event = new GitHubEvent(['action' => 'labeled', 'number' => 1234, 'pull_request' => []], $this->repository);
        $this->pullRequestApi->method('show')->willReturn([
            'title' => '[Platform] Foo Bar',  // Title already has one label
            'labels' => [
                ['name' => 'Platform', 'color' => 'dddddd'],
                ['name' => 'Agent', 'color' => 'dddddd'],  // New label added
            ],
        ]);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();
        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['pull_request']);
        $this->assertSame('[Agent][Platform] Foo Bar', $responseData['new_title']);
    }

    public function testLabelsNotRecognizedAsValidLabels()
    {
        // This test simulates what happens when [Platform] and [Agent] are in the title
        // but they're NOT recognized as valid labels (wrong case or not in label list)
        $event = new GitHubEvent(['action' => 'labeled', 'number' => 1234, 'pull_request' => []], $this->repository);
        $this->pullRequestApi->method('show')->willReturn([
            'title' => '[Platform] [Agent] Foo Bar',  // These are in title but not recognized
            'labels' => [
                ['name' => 'Bug', 'color' => 'dddddd'],  // Different valid label
            ],
        ]);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();
        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['pull_request']);
        // Since Platform and Agent are not recognized labels, they stay in the title without spaces
        $this->assertSame('[Bug][Platform][Agent] Foo Bar', $responseData['new_title']);
    }

    public function testBugWithLabelsNotInRepositoryLabelList()
    {
        $event = new GitHubEvent(['action' => 'labeled', 'number' => 1234, 'pull_request' => []], $this->repository);
        $this->pullRequestApi->method('show')->willReturn([
            'title' => 'Foo Bar',
            'labels' => [
                // These labels are attached to PR but not in the StaticLabelApi list
                ['name' => 'Platform', 'color' => 'dddddd'],
                ['name' => 'Agent', 'color' => 'dddddd'],
            ],
        ]);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();

        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['pull_request']);
        $this->assertSame('[Agent][Platform] Foo Bar', $responseData['new_title']);
    }

    /**
     * Test that ensures no spaces are ever added between label brackets.
     */
    public function testNoSpacesBetweenLabelBrackets()
    {
        // Test with empty title after label removal
        $event = new GitHubEvent(['action' => 'labeled', 'number' => 1234, 'pull_request' => []], $this->repository);
        $this->pullRequestApi->method('show')->willReturn([
            'title' => '[Console] [FrameworkBundle]',  // Only labels, no other text
            'labels' => [
                ['name' => 'Console', 'color' => 'dddddd'],
                ['name' => 'FrameworkBundle', 'color' => 'dddddd'],
            ],
        ]);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();

        // Should produce labels without spaces and no trailing space
        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['pull_request']);
        $this->assertSame('[Console][FrameworkBundle]', $responseData['new_title']);
    }

    /**
     * Test when title has unrecognized bracketed text like [Foo] that isn't a label.
     */
    public function testUnrecognizedBracketedTextWithNewLabel()
    {
        $event = new GitHubEvent(['action' => 'labeled', 'number' => 1234, 'pull_request' => []], $this->repository);
        $this->pullRequestApi->method('show')->willReturn([
            'title' => '[Foo] Bar',  // [Foo] is not a recognized label
            'labels' => [
                ['name' => 'Console', 'color' => 'dddddd'],  // Adding Console label
            ],
        ]);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();

        // Currently produces: [Console] [Foo] Bar (with space)
        // Should produce: [Console][Foo] Bar (no space between brackets)
        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['pull_request']);
        $this->assertSame('[Console][Foo] Bar', $responseData['new_title']);
    }

    /**
     * Test multiple unrecognized bracketed texts with real labels.
     */
    public function testMultipleUnrecognizedBracketsWithRealLabels()
    {
        $event = new GitHubEvent(['action' => 'labeled', 'number' => 1234, 'pull_request' => []], $this->repository);
        $this->pullRequestApi->method('show')->willReturn([
            'title' => '[TODO] [WIP] [Foo] Some title',  // [TODO], [WIP], [Foo] are not recognized labels
            'labels' => [
                ['name' => 'Console', 'color' => 'dddddd'],
                ['name' => 'FrameworkBundle', 'color' => 'dddddd'],
            ],
        ]);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);
        $responseData = $event->getResponseData();

        // Should keep unrecognized brackets but without spaces between any brackets
        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['pull_request']);
        $this->assertSame('[Console][FrameworkBundle][TODO][WIP][Foo] Some title', $responseData['new_title']);
    }
}
