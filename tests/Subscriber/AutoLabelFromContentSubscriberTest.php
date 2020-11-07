<?php

namespace App\Tests\Subscriber;

use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Repository\Repository;
use App\Service\LabelNameExtractor;
use App\Subscriber\AutoLabelFromContentSubscriber;
use App\Tests\Service\Issues\Github\FakedCachedLabelApi;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class AutoLabelFromContentSubscriberTest extends TestCase
{
    private $autoLabelSubscriber;

    private $labelsApi;

    private $repository;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    protected function setUp()
    {
        $this->labelsApi = $this->getMockBuilder(FakedCachedLabelApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['addIssueLabels'])
            ->getMock();
        $this->autoLabelSubscriber = new AutoLabelFromContentSubscriber($this->labelsApi, new LabelNameExtractor($this->labelsApi));
        $this->repository = new Repository('weaverryan', 'symfony', null);

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber($this->autoLabelSubscriber);
    }

    public function testAutoLabelIssue()
    {
        $this->labelsApi->expects($this->once())
            ->method('addIssueLabels')
            ->with(1234, ['Messenger'], $this->repository)
            ->willReturn(null);

        $event = new GitHubEvent([
            'action' => 'opened',
            'issue' => [
                'number' => 1234,
                'title' => '[Messenger] Foobar',
                'body' => 'Some content',
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::ISSUES);

        $responseData = $event->getResponseData();

        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['issue']);
        $this->assertSame(['Messenger'], $responseData['issue_labels']);
    }

    /**
     * @dataProvider getPRTests
     */
    public function testAutoLabelPR($prTitle, $prBody, array $expectedNewLabels)
    {
        $this->labelsApi->expects($this->once())
            ->method('addIssueLabels')
            ->with(1234, $expectedNewLabels, $this->repository)
            ->willReturn(null);

        $event = new GitHubEvent([
            'action' => 'opened',
            'pull_request' => [
                'number' => 1234,
                'title' => $prTitle,
                'body' => $prBody,
            ],
        ], $this->repository);

        $this->dispatcher->dispatch($event, GitHubEvents::PULL_REQUEST);

        $responseData = $event->getResponseData();

        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['pull_request']);
        $this->assertSame($expectedNewLabels, $responseData['pr_labels']);
    }

    public function getPRTests()
    {
        $tests = [];

        // nothing added automatically
        $tests[] = [
            'Cool new Feature!',
            <<<EOF
FOO
EOF
            ,
            [],
        ];

        $tests[] = [
            'I am fixing a bug!',
            <<<EOF
Well hi cool peeps!

| Q             | A
| ------------- | ---
| Branch?       | master
| Bug fix?      | yes
| New feature?  | no
| BC breaks?    | no
| Deprecations? | no
| Tests pass?   | yes
| Fixed tickets | n/a
| License       | MIT
| Doc PR        | n/a
EOF
            ,
            ['Bug'],
        ];

        $tests[] = [
            'Using all the auto-labels!',
            <<<EOF
Well hi cool peeps!

| Q             | A
| ------------- | ---
| Branch?       | master
| Bug fix?      | yes
| New feature?  | yes
| BC breaks?    | yes
| Deprecations? | yes
| Tests pass?   | yes
| Fixed tickets | n/a
| License       | MIT
| Doc PR        | n/a
EOF
            ,
            ['Bug', 'Feature', 'BC Break', 'Deprecation'],
        ];

        $tests[] = [
            'case-insensitive!',
            <<<EOF
Well hi cool peeps!

| Q             | A
| ------------- | ---
| Branch?       | master
| Bug fix?      | YeS
| New feature?  | yEs
| BC breaks?    | yES
| Deprecations? | yeS
| Tests pass?   | yes
| Fixed tickets | n/a
| License       | MIT
| Doc PR        | n/a
EOF
            ,
            ['Bug', 'Feature', 'BC Break', 'Deprecation'],
        ];

        $tests[] = [
            '[Asset][bc Break][Fake] Extracting from title [Bug]',
            <<<EOF
EOF
            ,
            ['Asset', 'BC Break', 'Bug'],
        ];

        // testing the fixLabelName: di -> DependencyInjection
        $tests[] = [
            '[di][Fake] Extracting from title [Bug]',
            <<<EOF
EOF
            ,
            ['DependencyInjection', 'Bug'],
        ];

        return $tests;
    }
}
