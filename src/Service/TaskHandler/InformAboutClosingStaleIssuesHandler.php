<?php

declare(strict_types=1);

namespace App\Service\TaskHandler;

use App\Api\Issue\IssueApi;
use App\Api\Label\LabelApi;
use App\Command\PingStaleIssuesCommand;
use App\Entity\Task;
use App\Service\RepositoryProvider;
use App\Service\StaleIssueCommentGenerator;
use App\Service\TaskScheduler;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class InformAboutClosingStaleIssuesHandler implements TaskHandlerInterface
{
    private $issueApi;
    private $repositoryProvider;
    private $labelApi;
    private $commentGenerator;
    private $scheduler;

    public function __construct(LabelApi $labelApi, IssueApi $issueApi, RepositoryProvider $repositoryProvider, StaleIssueCommentGenerator $commentGenerator, TaskScheduler $scheduler)
    {
        $this->issueApi = $issueApi;
        $this->repositoryProvider = $repositoryProvider;
        $this->labelApi = $labelApi;
        $this->commentGenerator = $commentGenerator;
        $this->scheduler = $scheduler;
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
            $this->issueApi->commentOnIssue($repository, $task->getNumber(), $this->commentGenerator->getInformAboutClosingComment());
            $this->scheduler->runLater($repository, $task->getNumber(), Task::ACTION_CLOSE_STALE, new \DateTimeImmutable(PingStaleIssuesCommand::MESSAGE_THREE_AND_CLOSE_AFTER));
        } else {
            $this->labelApi->removeIssueLabel($task->getNumber(), 'Stalled', $repository);
        }
    }

    public function supports(Task $task): bool
    {
        return Task::ACTION_INFORM_CLOSE_STALE === $task->getAction();
    }
}
