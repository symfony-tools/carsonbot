<?php

declare(strict_types=1);

namespace App\Service\TaskHandler;

use App\Api\Issue\IssueApi;
use App\Api\PullRequest\PullRequestApi;
use App\Entity\Task;
use App\Service\RepositoryProvider;
use Psr\Log\LoggerInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class CloseDraftHandler implements TaskHandlerInterface
{
    public function __construct(
        private readonly PullRequestApi $pullRequestApi,
        private readonly IssueApi $issueApi,
        private readonly RepositoryProvider $repositoryProvider,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(Task $task): void
    {
        $repository = $this->repositoryProvider->getRepository($task->getRepositoryFullName());
        if (!$repository) {
            $this->logger->error(sprintf('RepositoryProvider returned nothing for "%s" ', $task->getRepositoryFullName()));

            return;
        }

        $pr = $this->pullRequestApi->show($repository, $task->getNumber());
        if ($pr['draft'] ?? false) {
            $this->issueApi->close($repository, $task->getNumber());
        }
    }

    public function supports(Task $task): bool
    {
        return Task::ACTION_CLOSE_DRAFT === $task->getAction();
    }
}
