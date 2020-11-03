<?php

namespace App\Service;

use App\Entity\Task;
use App\Repository\TaskRepository;
use App\Service\TaskHandler\TaskHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class TaskRunner
{
    /**
     * @var iterable<TaskHandlerInterface>
     */
    private $handlers;
    private $repository;
    private $logger;

    public function __construct(TaskRepository $repository, iterable $handlers, LoggerInterface $logger)
    {
        $this->handlers = $handlers;
        $this->repository = $repository;
        $this->logger = $logger;
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
