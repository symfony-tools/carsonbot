<?php

declare(strict_types=1);

namespace Service\TaskHandler;

use App\Api\Issue\NullIssueApi;
use App\Api\Label\NullLabelApi;
use App\Entity\Task;
use App\Service\RepositoryProvider;
use App\Service\StaleIssueCommentGenerator;
use App\Service\TaskHandler\CloseStaleIssuesHandler;
use PHPUnit\Framework\TestCase;

class CloseStaleIssuesHandlerTest extends TestCase
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
            ->setMethods(['close', 'lastCommentWasMadeByBot'])
            ->getMock();
        $issueApi->expects($this->any())->method('lastCommentWasMadeByBot')->willReturn(true);
        $issueApi->expects($this->never())->method('close');

        $repoProvider = new RepositoryProvider(['carsonbot-playground/symfony' => []]);

        $handler = new CloseStaleIssuesHandler($labelApi, $issueApi, $repoProvider, new StaleIssueCommentGenerator());
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
            ->setMethods(['close', 'lastCommentWasMadeByBot'])
            ->getMock();
        $issueApi->expects($this->any())->method('lastCommentWasMadeByBot')->willReturn(false);
        $issueApi->expects($this->never())->method('close');

        $repoProvider = new RepositoryProvider(['carsonbot-playground/symfony' => []]);

        $handler = new CloseStaleIssuesHandler($labelApi, $issueApi, $repoProvider, new StaleIssueCommentGenerator());
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
            ->setMethods(['close', 'lastCommentWasMadeByBot'])
            ->getMock();
        $issueApi->expects($this->any())->method('lastCommentWasMadeByBot')->willReturn(true);
        $issueApi->expects($this->once())->method('close');

        $repoProvider = new RepositoryProvider(['carsonbot-playground/symfony' => []]);

        $handler = new CloseStaleIssuesHandler($labelApi, $issueApi, $repoProvider, new StaleIssueCommentGenerator());
        $handler->handle(new Task('carsonbot-playground/symfony', 4711, Task::ACTION_CLOSE_STALE, new \DateTimeImmutable()));
    }
}
