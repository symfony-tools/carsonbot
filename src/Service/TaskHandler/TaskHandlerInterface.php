<?php

namespace App\Service\TaskHandler;

use App\Entity\Task;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
interface TaskHandlerInterface
{
    public function handle(Task $task): void;

    public function supports(Task $task): bool;
}
