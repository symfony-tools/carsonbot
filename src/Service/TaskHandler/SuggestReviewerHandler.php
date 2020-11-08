<?php

declare(strict_types=1);

namespace App\Service\TaskHandler;

use App\Api\Issue\IssueApi;
use App\Api\PullRequest\PullRequestApi;
use App\Api\Status\Status;
use App\Api\Status\StatusApi;
use App\Command\SuggestReviewerCommand;
use App\Entity\Task;
use App\Service\RepositoryProvider;
use App\Service\TaskScheduler;
use Psr\Log\LoggerInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class SuggestReviewerHandler implements TaskHandlerInterface
{
    private $issueApi;
    private $repositoryProvider;
    private $pullRequestApi;
    private $statusApi;
    private $scheduler;
    private $logger;

    public function __construct(PullRequestApi $pullRequestApi, IssueApi $issueApi, RepositoryProvider $repositoryProvider, StatusApi $statusApi, TaskScheduler $scheduler, LoggerInterface $logger)
    {
        $this->issueApi = $issueApi;
        $this->repositoryProvider = $repositoryProvider;
        $this->pullRequestApi = $pullRequestApi;
        $this->statusApi = $statusApi;
        $this->scheduler = $scheduler;
        $this->logger = $logger;
    }

    /**
     * We want to check if the PR has no activity, then suggest a reviewer.
     */
    public function handle(Task $task): void
    {
        $repository = $this->repositoryProvider->getRepository($task->getRepositoryFullName());
        if (!$repository) {
            $this->logger->error(sprintf('RepositoryProvider returned nothing for "%s" ', $task->getRepositoryFullName()));

            return;
        }

        if ($this->issueApi->hasActivity($repository, $task->getNumber())) {
            return;
        }

        $status = $this->statusApi->getIssueStatus($task->getNumber(), $repository);
        if (Status::NEEDS_REVIEW === $status || null === $status) {
            $this->pullRequestApi->findReviewer($repository, $task->getNumber(), SuggestReviewerCommand::TYPE_SUGGEST);
        } else {
            // Try again later
            $this->scheduler->runLater($repository, $task->getNumber(), Task::ACTION_SUGGEST_REVIEWER, new \DateTimeImmutable('+20hours'));
        }
    }

    public function supports(Task $task): bool
    {
        return Task::ACTION_SUGGEST_REVIEWER === $task->getAction();
    }
}
