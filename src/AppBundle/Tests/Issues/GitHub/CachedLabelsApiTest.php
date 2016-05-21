<?php

namespace AppBundle\Tests\Issues\GitHub;

use AppBundle\Issues\GitHub\CachedLabelsApi;
use AppBundle\Repository\Repository;
use Github\Api\Issue\Labels;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class CachedLabelsApiTest extends \PHPUnit_Framework_TestCase
{
    const USER_NAME = 'weaverryan';

    const REPO_NAME = 'carson';

    /**
     * @var Labels|\PHPUnit_Framework_MockObject_MockObject
     */
    private $backendApi;

    /**
     * @var CachedLabelsApi
     */
    private $api;

    private $repository;

    protected function setUp()
    {
        $this->backendApi = $this->getMockBuilder('Github\Api\Issue\Labels')
            ->disableOriginalConstructor()
            ->getMock();
        $this->api = new CachedLabelsApi($this->backendApi);
        $this->repository = new Repository(
            self::USER_NAME,
            self::REPO_NAME,
            [],
            null
        );
    }

    public function testGetIssueLabels()
    {
        $this->backendApi->expects($this->once())
            ->method('all')
            ->with(self::USER_NAME, self::REPO_NAME, 1234)
            ->willReturn(array(
                array('name' => 'a'),
                array('name' => 'b'),
                array('name' => 'c'),
            ));

        $this->assertSame(array('a', 'b', 'c'), $this->api->getIssueLabels(1234, $this->repository));

        // Subsequent access goes to cache
        $this->assertSame(array('a', 'b', 'c'), $this->api->getIssueLabels(1234, $this->repository));
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
            ->willReturn(array(
                array('name' => 'a'),
                array('name' => 'b'),
                array('name' => 'c'),
            ));

        $this->backendApi->expects($this->once())
            ->method('add')
            ->with(self::USER_NAME, self::REPO_NAME, 1234, 'd');

        $this->assertSame(array('a', 'b', 'c'), $this->api->getIssueLabels(1234, $this->repository));

        $this->api->addIssueLabel(1234, 'd', $this->repository);

        $this->assertSame(array('a', 'b', 'c', 'd'), $this->api->getIssueLabels(1234, $this->repository));
    }

    public function testAddIssueLabelIgnoresDuplicate()
    {
        $this->backendApi->expects($this->once())
            ->method('all')
            ->with(self::USER_NAME, self::REPO_NAME, 1234)
            ->willReturn(array(
                array('name' => 'a'),
                array('name' => 'b'),
                array('name' => 'c'),
            ));

        $this->backendApi->expects($this->never())
            ->method('add');

        $this->assertSame(array('a', 'b', 'c'), $this->api->getIssueLabels(1234, $this->repository));

        $this->api->addIssueLabel(1234, 'c', $this->repository);

        $this->assertSame(array('a', 'b', 'c'), $this->api->getIssueLabels(1234, $this->repository));
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
            ->willReturn(array(
                array('name' => 'a'),
                array('name' => 'b'),
                array('name' => 'c'),
            ));

        $this->backendApi->expects($this->once())
            ->method('remove')
            ->with(self::USER_NAME, self::REPO_NAME, 1234, 'a');

        $this->assertSame(array('a', 'b', 'c'), $this->api->getIssueLabels(1234, $this->repository));

        $this->api->removeIssueLabel(1234, 'a', $this->repository);

        $this->assertSame(array('b', 'c'), $this->api->getIssueLabels(1234, $this->repository));
    }

    public function testRemoveIssueLabelIgnoresUnsetLabel()
    {
        $this->backendApi->expects($this->once())
            ->method('all')
            ->with(self::USER_NAME, self::REPO_NAME, 1234)
            ->willReturn(array(
                array('name' => 'a'),
                array('name' => 'b'),
                array('name' => 'c'),
            ));

        $this->backendApi->expects($this->never())
            ->method('remove');

        $this->assertSame(array('a', 'b', 'c'), $this->api->getIssueLabels(1234, $this->repository));

        $this->api->removeIssueLabel(1234, 'd', $this->repository);

        $this->assertSame(array('a', 'b', 'c'), $this->api->getIssueLabels(1234, $this->repository));
    }
}
