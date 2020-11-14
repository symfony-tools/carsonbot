<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Task;
use App\Model\Repository;
use App\Repository\TaskRepository;

/**
 * Schedule a job to run later.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class TaskScheduler
{
    private $taskRepo;

    public function __construct(TaskRepository $taskRepo)
    {
        $this->taskRepo = $taskRepo;
    }

    public function runLater(Repository $repository, $number, int $action, \DateTimeImmutable $checkAt)
    {
        $task = new Task($repository->getFullName(), $number, $action, $checkAt);
        $this->taskRepo->persist($task);
        $this->taskRepo->flush();
    }
}
