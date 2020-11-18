<?php

declare(strict_types=1);

namespace Service\TaskHandler;

use App\Api\Issue\NullIssueApi;
use App\Api\PullRequest\NullPullRequestApi;
use App\Entity\Task;
use App\Service\RepositoryProvider;
use App\Service\TaskHandler\CloseDraftHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class CloseDraftHandlerTest extends TestCase
{
    public function testHandleStillInDraft()
    {
        $prApi = $this->getMockBuilder(NullPullRequestApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['show'])
            ->getMock();
        $prApi->expects($this->once())->method('show')->willReturn(['draft' => true]);

        $issueApi = $this->getMockBuilder(NullIssueApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['close'])
            ->getMock();
        $issueApi->expects($this->once())->method('close');

        $repoProvider = new RepositoryProvider(['carsonbot-playground/symfony' => []]);

        $handler = new CloseDraftHandler($prApi, $issueApi, $repoProvider, new NullLogger());
        $handler->handle(new Task('carsonbot-playground/symfony', 4711, Task::ACTION_CLOSE_DRAFT, new \DateTimeImmutable()));
    }

    public function testHandleNotDraft()
    {
        $prApi = $this->getMockBuilder(NullPullRequestApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['show'])
            ->getMock();
        $prApi->expects($this->once())->method('show')->willReturn(['draft' => false]);

        $issueApi = $this->getMockBuilder(NullIssueApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['close'])
            ->getMock();
        $issueApi->expects($this->never())->method('close');

        $repoProvider = new RepositoryProvider(['carsonbot-playground/symfony' => []]);

        $handler = new CloseDraftHandler($prApi, $issueApi, $repoProvider, new NullLogger());
        $handler->handle(new Task('carsonbot-playground/symfony', 4711, Task::ACTION_CLOSE_DRAFT, new \DateTimeImmutable()));
    }
}
