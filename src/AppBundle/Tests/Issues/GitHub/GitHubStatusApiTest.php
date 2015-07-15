<?php

namespace AppBundle\Tests\Issues\GitHub;

use AppBundle\Issues\GitHub\CachedLabelsApi;
use AppBundle\Issues\GitHub\GitHubStatusApi;
use AppBundle\Issues\Status;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class GitHubStatusApiTest extends \PHPUnit_Framework_TestCase
{
    const USER_NAME = 'weaverryan';

    const REPO_NAME = 'carson';

    /**
     * @var CachedLabelsApi|\PHPUnit_Framework_MockObject_MockObject
     */
    private $labelsApi;

    /**
     * @var GitHubStatusApi
     */
    private $api;

    protected function setUp()
    {
        $this->labelsApi = $this->getMockBuilder('AppBundle\Issues\GitHub\CachedLabelsApi')
            ->disableOriginalConstructor()
            ->getMock();
        $this->api = new GitHubStatusApi($this->labelsApi, self::USER_NAME, self::REPO_NAME);
    }

    public function testSetIssueStatus()
    {
        $this->labelsApi->expects($this->once())
            ->method('getIssueLabels')
            ->with(1234)
            ->willReturn(array('Bug', 'Status: Needs Review'));

        $this->labelsApi->expects($this->once())
            ->method('removeIssueLabel')
            ->with(1234, 'Status: Needs Review');

        $this->labelsApi->expects($this->once())
            ->method('addIssueLabel')
            ->with(1234, 'Status: Reviewed');

        $this->api->setIssueStatus(1234, Status::REVIEWED);
    }

    public function testSetIssueStatusWithoutPreviousStatus()
    {
        $this->labelsApi->expects($this->once())
            ->method('getIssueLabels')
            ->with(1234)
            ->willReturn(array('Bug'));

        $this->labelsApi->expects($this->never())
            ->method('removeIssueLabel');

        $this->labelsApi->expects($this->once())
            ->method('addIssueLabel')
            ->with(1234, 'Status: Reviewed');

        $this->api->setIssueStatus(1234, Status::REVIEWED);
    }

    public function testSetIssueStatusRemovesExcessStatuses()
    {
        $this->labelsApi->expects($this->at(0))
            ->method('getIssueLabels')
            ->with(1234)
            ->willReturn(array('Bug', 'Status: Needs Review', 'Status: Needs Work'));

        $this->labelsApi->expects($this->at(1))
            ->method('removeIssueLabel')
            ->with(1234, 'Status: Needs Review');

        $this->labelsApi->expects($this->at(2))
            ->method('removeIssueLabel')
            ->with(1234, 'Status: Needs Work');

        $this->labelsApi->expects($this->at(3))
            ->method('addIssueLabel')
            ->with(1234, 'Status: Reviewed');

        $this->api->setIssueStatus(1234, Status::REVIEWED);
    }

    public function testSetIssueStatusDoesNothingIfAlreadySet()
    {
        $this->labelsApi->expects($this->once())
            ->method('getIssueLabels')
            ->with(1234)
            ->willReturn(array('Bug', 'Status: Needs Review'));

        $this->labelsApi->expects($this->never())
            ->method('removeIssueLabel');

        $this->labelsApi->expects($this->never())
            ->method('addIssueLabel');

        $this->api->setIssueStatus(1234, Status::NEEDS_REVIEW);
    }

    public function testSetIssueStatusRemovesExcessLabelsIfAlreadySet()
    {
        $this->labelsApi->expects($this->once())
            ->method('getIssueLabels')
            ->with(1234)
            ->willReturn(array('Bug', 'Status: Needs Review', 'Status: Reviewed'));

        $this->labelsApi->expects($this->once())
            ->method('removeIssueLabel')
            ->with(1234, 'Status: Needs Review');

        $this->labelsApi->expects($this->never())
            ->method('addIssueLabel');

        $this->api->setIssueStatus(1234, Status::REVIEWED);
    }

    public function testGetIssueStatus()
    {
        $this->labelsApi->expects($this->once())
            ->method('getIssueLabels')
            ->with(1234)
            ->willReturn(array('Bug', 'Status: Needs Review'));

        $this->assertSame(Status::NEEDS_REVIEW, $this->api->getIssueStatus(1234));
    }

    public function testGetIssueStatusReturnsFirst()
    {
        $this->labelsApi->expects($this->once())
            ->method('getIssueLabels')
            ->with(1234)
            ->willReturn(array('Bug', 'Status: Needs Review', 'Status: Reviewed'));

        $this->assertSame(Status::NEEDS_REVIEW, $this->api->getIssueStatus(1234));
    }

    public function testGetIssueStatusReturnsNullIfNoneSet()
    {
        $this->labelsApi->expects($this->once())
            ->method('getIssueLabels')
            ->with(1234)
            ->willReturn(array('Bug'));

        $this->assertNull($this->api->getIssueStatus(1234));
    }

    public function testGetNeedsReviewUrl()
    {
        $this->assertSame('https://github.com/weaverryan/carson/labels/Status%3A%20Needs%20Review', $this->api->getNeedsReviewUrl());
    }
}
