<?php

namespace AppBundle\Tests\Subscriber;

use AppBundle\Event\GitHubEvent;
use AppBundle\GitHubEvents;
use AppBundle\Repository\Repository;
use AppBundle\Subscriber\AutoLabelPRFromContentSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcher;

class AutoLabelPRFromContentSubscriberTest extends \PHPUnit_Framework_TestCase
{
    private $autoLabelSubscriber;

    private $labelsApi;

    private $repository;

    /**
     * @var EventDispatcher
     */
    private static $dispatcher;

    public static function setUpBeforeClass()
    {
        self::$dispatcher = new EventDispatcher();
    }

    protected function setUp()
    {
        $this->labelsApi = $this->getMockBuilder('AppBundle\Issues\GitHub\CachedLabelsApi')
            ->disableOriginalConstructor()
            ->getMock();
        $this->autoLabelSubscriber = new AutoLabelPRFromContentSubscriber($this->labelsApi);
        $this->repository = new Repository('weaverryan', 'symfony', [], null);

        self::$dispatcher->addSubscriber($this->autoLabelSubscriber);
    }

    /**
     * @dataProvider getPRTests
     */
    public function testAutoLabel($prTitle, $prBody, array $expectedNewLabels)
    {
        $this->labelsApi->expects($this->once())
            ->method('addIssueLabels')
            ->with(1234, $expectedNewLabels, $this->repository)
            ->willReturn(null);

        $event = new GitHubEvent(array(
            'action' => 'opened',
            'pull_request' => array(
                'number' => 1234,
                'title' => $prTitle,
                'body' => $prBody,
            ),
        ), $this->repository);

        self::$dispatcher->dispatch(GitHubEvents::PULL_REQUEST, $event);

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
