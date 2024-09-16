<?php

namespace App\Service;

use App\Entity\Task;
use App\Repository\TaskRepository;
use App\Service\TaskHandler\TaskHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
readonly class TaskRunner
{
    /**
     * @param iterable<TaskHandlerInterface> $handlers
     */
    public function __construct(
        private TaskRepository $repository,
        private iterable $handlers,
        private LoggerInterface $logger,
    ) {
    }

    public function run(Task $task)
    {
        try {
            $this->doRun($task);
        } finally {
            $this->repository->remove($task);
            $this->repository->flush();
        }
    }

    private function doRun(Task $task): void
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($task)) {
                $handler->handle($task);

                return;
            }
        }

        $this->logger->error('Task was never handled by a TaskHandler', [
            'repository' => $task->getRepositoryFullName(),
            'number' => $task->getNumber(),
            'action' => $task->getAction(),
        ]);
    }
}
