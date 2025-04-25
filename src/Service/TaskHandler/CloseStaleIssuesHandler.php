<?php

declare(strict_types=1);

namespace App\Service\TaskHandler;

use App\Api\Issue\IssueApi;
use App\Api\Issue\StateReason;
use App\Api\Label\LabelApi;
use App\Entity\Task;
use App\Service\RepositoryProvider;
use App\Service\StaleIssueCommentGenerator;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class CloseStaleIssuesHandler implements TaskHandlerInterface
{
    public function __construct(
        private readonly LabelApi $labelApi,
        private readonly IssueApi $issueApi,
        private readonly RepositoryProvider $repositoryProvider,
        private readonly StaleIssueCommentGenerator $commentGenerator,
    ) {
    }

    /**
     * Close the issue if the last comment was made by the bot and if "Keep open" label does not exist.
     */
    public function handle(Task $task): void
    {
        if (null === $repository = $this->repositoryProvider->getRepository($task->getRepositoryFullName())) {
            return;
        }

        $issue = $this->issueApi->show($repository, $task->getNumber());
        if ('open' !== $issue['state']) {
            return;
        }

        $labels = $this->labelApi->getIssueLabels($task->getNumber(), $repository);
        if (in_array('Keep open', $labels)) {
            $this->labelApi->removeIssueLabel($task->getNumber(), 'Stalled', $repository);

            return;
        }

        if ($this->issueApi->lastCommentWasMadeByBot($repository, $task->getNumber())) {
            $this->issueApi->commentOnIssue($repository, $task->getNumber(), $this->commentGenerator->getClosingComment());
            $this->issueApi->close($repository, $task->getNumber(), StateReason::NOT_PLANNED);
        } else {
            $this->labelApi->removeIssueLabel($task->getNumber(), 'Stalled', $repository);
        }
    }

    public function supports(Task $task): bool
    {
        return Task::ACTION_CLOSE_STALE === $task->getAction();
    }
}
