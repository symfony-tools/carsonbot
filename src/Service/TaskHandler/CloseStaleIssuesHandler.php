<?php

declare(strict_types=1);

namespace App\Service\TaskHandler;

use App\Api\Issue\IssueApi;
use App\Api\Label\LabelApi;
use App\Entity\Task;
use App\Service\RepositoryProvider;
use App\Service\StaleIssueCommentGenerator;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class CloseStaleIssuesHandler implements TaskHandlerInterface
{
    private $issueApi;
    private $repositoryProvider;
    private $labelApi;
    private $commentGenerator;

    public function __construct(LabelApi $labelApi, IssueApi $issueApi, RepositoryProvider $repositoryProvider, StaleIssueCommentGenerator $commentGenerator)
    {
        $this->issueApi = $issueApi;
        $this->repositoryProvider = $repositoryProvider;
        $this->labelApi = $labelApi;
        $this->commentGenerator = $commentGenerator;
    }

    /**
     * Close the issue if the last comment was made by the bot and if "Keep open" label does not exist.
     */
    public function handle(Task $task): void
    {
        if (null === $repository = $this->repositoryProvider->getRepository($task->getRepositoryFullName())) {
            return;
        }
        $labels = $this->labelApi->getIssueLabels($task->getNumber(), $repository);
        if (in_array('Keep open', $labels)) {
            $this->labelApi->removeIssueLabel($task->getNumber(), 'Stalled', $repository);

            return;
        }

        if ($this->issueApi->lastCommentWasMadeByBot($repository, $task->getNumber())) {
            $this->issueApi->commentOnIssue($repository, $task->getNumber(), $this->commentGenerator->getClosingComment());
            $this->issueApi->close($repository, $task->getNumber());
        } else {
            $this->labelApi->removeIssueLabel($task->getNumber(), 'Stalled', $repository);
        }
    }

    public function supports(Task $task): bool
    {
        return Task::ACTION_CLOSE_STALE === $task->getAction();
    }
}
