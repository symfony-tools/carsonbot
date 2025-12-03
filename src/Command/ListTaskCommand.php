<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Task;
use App\Repository\TaskRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
#[AsCommand(
    name: 'app:task:list',
    description: 'List scheduled tasks',
)]
final class ListTaskCommand
{
    public function __construct(
        private readonly TaskRepository $repository,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option]
        ?int $number = null,
    ): int {
        if (null === $number) {
            $criteria = [];
        } else {
            $criteria = ['number' => $number];
        }

        $limit = 100;
        /** @var Task[] $tasks */
        $tasks = $this->repository->findBy($criteria, ['verifyAfter' => 'ASC'], $limit);
        $rows = [];
        foreach ($tasks as $task) {
            $rows[] = [
                $task->getRepositoryFullName(),
                $task->getNumber(),
                $this->actionToString($task->getAction()),
                $task->getVerifyAfter()->format('Y-m-d H:i:s'),
            ];
        }

        $io->table(['Repo', 'Number', 'Action', 'Verify After'], $rows);
        $io->write(sprintf('Total of %d items in the table. ', $taskCount = count($tasks)));
        if ($limit === $taskCount) {
            $io->write('There might be more tasks in the database.');
        }
        $io->newLine();

        return Command::SUCCESS;
    }

    private function actionToString(int $action): string
    {
        $data = [
            Task::ACTION_CLOSE_STALE => 'Close stale',
            Task::ACTION_CLOSE_DRAFT => 'Close draft',
            Task::ACTION_INFORM_CLOSE_STALE => 'Inform about close',
        ];

        return $data[$action] ?? 'Unknown action';
    }
}
