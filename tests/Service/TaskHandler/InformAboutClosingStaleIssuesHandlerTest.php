<?php

declare(strict_types=1);

namespace App\Tests\Service\TaskHandler;

use App\Api\Issue\NullIssueApi;
use App\Api\Label\NullLabelApi;
use App\Entity\Task;
use App\Service\RepositoryProvider;
use App\Service\StaleIssueCommentGenerator;
use App\Service\TaskHandler\InformAboutClosingStaleIssuesHandler;
use App\Service\TaskScheduler;
use PHPUnit\Framework\TestCase;

class InformAboutClosingStaleIssuesHandlerTest extends TestCase
{
    public function testHandleKeepOpen()
    {
        $labelApi = $this->getMockBuilder(NullLabelApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIssueLabels', 'lastCommentWasMadeByBot'])
            ->getMock();
        $labelApi->expects($this->any())->method('getIssueLabels')->willReturn(['Bug', 'Keep open']);

        $issueApi = $this->getMockBuilder(NullIssueApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['close', 'lastCommentWasMadeByBot', 'show'])
            ->getMock();
        $issueApi->expects($this->any())->method('show')->willReturn(['state' => 'open']);
        $issueApi->expects($this->any())->method('lastCommentWasMadeByBot')->willReturn(true);
        $issueApi->expects($this->never())->method('close');

        $scheduler = $this->getMockBuilder(TaskScheduler::class)
            ->disableOriginalConstructor()
            ->setMethods(['runLater'])
            ->getMock();
        $scheduler->expects($this->never())->method('runLater');

        $repoProvider = new RepositoryProvider(['carsonbot-playground/symfony' => []]);

        $handler = new InformAboutClosingStaleIssuesHandler($labelApi, $issueApi, $repoProvider, new StaleIssueCommentGenerator(), $scheduler);
        $handler->handle(new Task('carsonbot-playground/symfony', 4711, Task::ACTION_CLOSE_STALE, new \DateTimeImmutable()));
    }

    public function testHandleComments()
    {
        $labelApi = $this->getMockBuilder(NullLabelApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIssueLabels', 'lastCommentWasMadeByBot'])
            ->getMock();
        $labelApi->expects($this->any())->method('getIssueLabels')->willReturn(['Bug']);

        $issueApi = $this->getMockBuilder(NullIssueApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['close', 'lastCommentWasMadeByBot', 'show'])
            ->getMock();
        $issueApi->expects($this->any())->method('show')->willReturn(['state' => 'open']);
        $issueApi->expects($this->any())->method('lastCommentWasMadeByBot')->willReturn(false);
        $issueApi->expects($this->never())->method('close');

        $scheduler = $this->getMockBuilder(TaskScheduler::class)
            ->disableOriginalConstructor()
            ->setMethods(['runLater'])
            ->getMock();
        $scheduler->expects($this->never())->method('runLater');

        $repoProvider = new RepositoryProvider(['carsonbot-playground/symfony' => []]);

        $handler = new InformAboutClosingStaleIssuesHandler($labelApi, $issueApi, $repoProvider, new StaleIssueCommentGenerator(), $scheduler);
        $handler->handle(new Task('carsonbot-playground/symfony', 4711, Task::ACTION_CLOSE_STALE, new \DateTimeImmutable()));
    }

    public function testHandleStale()
    {
        $labelApi = $this->getMockBuilder(NullLabelApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIssueLabels'])
            ->getMock();
        $labelApi->expects($this->any())->method('getIssueLabels')->willReturn(['Bug']);

        $issueApi = $this->getMockBuilder(NullIssueApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['close', 'lastCommentWasMadeByBot', 'show'])
            ->getMock();
        $issueApi->expects($this->any())->method('show')->willReturn(['state' => 'open']);
        $issueApi->expects($this->any())->method('lastCommentWasMadeByBot')->willReturn(true);
        $issueApi->expects($this->never())->method('close');

        $scheduler = $this->getMockBuilder(TaskScheduler::class)
            ->disableOriginalConstructor()
            ->setMethods(['runLater'])
            ->getMock();
        $scheduler->expects($this->once())->method('runLater');

        $repoProvider = new RepositoryProvider(['carsonbot-playground/symfony' => []]);

        $handler = new InformAboutClosingStaleIssuesHandler($labelApi, $issueApi, $repoProvider, new StaleIssueCommentGenerator(), $scheduler);
        $handler->handle(new Task('carsonbot-playground/symfony', 4711, Task::ACTION_CLOSE_STALE, new \DateTimeImmutable()));
    }
}
