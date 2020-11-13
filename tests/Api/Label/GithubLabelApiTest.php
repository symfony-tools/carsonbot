<?php

namespace App\Tests\Api\Label;

use App\Api\Label\GithubLabelApi;
use App\Model\Repository;
use Github\Api\Issue\Labels;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\NullAdapter;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class GithubLabelApiTest extends TestCase
{
    const USER_NAME = 'weaverryan';

    const REPO_NAME = 'carson';

    /**
     * @var Labels|MockObject
     */
    private $backendApi;

    /**
     * @var GithubLabelApi
     */
    private $api;

    private $repository;

    protected function setUp()
    {
        $this->backendApi = $this->getMockBuilder(Labels::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->api = new GithubLabelApi($this->backendApi, new NullAdapter(), new NullLogger());
        $this->repository = new Repository(
            self::USER_NAME,
            self::REPO_NAME,
            null
        );
    }

    public function testGetIssueLabels()
    {
        $this->backendApi->expects($this->once())
            ->method('all')
            ->with(self::USER_NAME, self::REPO_NAME, 1234)
            ->willReturn([
                ['name' => 'a'],
                ['name' => 'b'],
                ['name' => 'c'],
            ]);

        $this->assertSame(['a', 'b', 'c'], $this->api->getIssueLabels(1234, $this->repository));

        // Subsequent access goes to cache
        $this->assertSame(['a', 'b', 'c'], $this->api->getIssueLabels(1234, $this->repository));
    }

    public function testAddIssueLabel()
    {
        $this->backendApi->expects($this->never())
            ->method('all');

        $this->backendApi->expects($this->once())
            ->method('add')
            ->with(self::USER_NAME, self::REPO_NAME, 1234, 'a');

        $this->api->addIssueLabel(1234, 'a', $this->repository);
    }

    public function testAddIssueLabelUpdatesCache()
    {
        $this->backendApi->expects($this->once())
            ->method('all')
            ->with(self::USER_NAME, self::REPO_NAME, 1234)
            ->willReturn([
                ['name' => 'a'],
                ['name' => 'b'],
                ['name' => 'c'],
            ]);

        $this->backendApi->expects($this->once())
            ->method('add')
            ->with(self::USER_NAME, self::REPO_NAME, 1234, 'd');

        $this->assertSame(['a', 'b', 'c'], $this->api->getIssueLabels(1234, $this->repository));

        $this->api->addIssueLabel(1234, 'd', $this->repository);

        $this->assertSame(['a', 'b', 'c', 'd'], $this->api->getIssueLabels(1234, $this->repository));
    }

    public function testAddIssueLabelIgnoresDuplicate()
    {
        $this->backendApi->expects($this->once())
            ->method('all')
            ->with(self::USER_NAME, self::REPO_NAME, 1234)
            ->willReturn([
                ['name' => 'a'],
                ['name' => 'b'],
                ['name' => 'c'],
            ]);

        $this->backendApi->expects($this->never())
            ->method('add');

        $this->assertSame(['a', 'b', 'c'], $this->api->getIssueLabels(1234, $this->repository));

        $this->api->addIssueLabel(1234, 'c', $this->repository);

        $this->assertSame(['a', 'b', 'c'], $this->api->getIssueLabels(1234, $this->repository));
    }

    public function testRemoveIssueLabel()
    {
        $this->backendApi->expects($this->never())
            ->method('all');

        $this->backendApi->expects($this->once())
            ->method('remove')
            ->with(self::USER_NAME, self::REPO_NAME, 1234, 'a');

        $this->api->removeIssueLabel(1234, 'a', $this->repository);
    }

    public function testRemoveIssueLabelUpdatesCache()
    {
        $this->backendApi->expects($this->once())
            ->method('all')
            ->with(self::USER_NAME, self::REPO_NAME, 1234)
            ->willReturn([
                ['name' => 'a'],
                ['name' => 'b'],
                ['name' => 'c'],
            ]);

        $this->backendApi->expects($this->once())
            ->method('remove')
            ->with(self::USER_NAME, self::REPO_NAME, 1234, 'a');

        $this->assertSame(['a', 'b', 'c'], $this->api->getIssueLabels(1234, $this->repository));

        $this->api->removeIssueLabel(1234, 'a', $this->repository);

        $this->assertSame(['b', 'c'], $this->api->getIssueLabels(1234, $this->repository));
    }

    public function testRemoveIssueLabelIgnoresUnsetLabel()
    {
        $this->backendApi->expects($this->once())
            ->method('all')
            ->with(self::USER_NAME, self::REPO_NAME, 1234)
            ->willReturn([
                ['name' => 'a'],
                ['name' => 'b'],
                ['name' => 'c'],
            ]);

        $this->backendApi->expects($this->never())
            ->method('remove');

        $this->assertSame(['a', 'b', 'c'], $this->api->getIssueLabels(1234, $this->repository));

        $this->api->removeIssueLabel(1234, 'd', $this->repository);

        $this->assertSame(['a', 'b', 'c'], $this->api->getIssueLabels(1234, $this->repository));
    }
}
