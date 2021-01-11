<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Task;
use App\Repository\TaskRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class ListTaskCommand extends Command
{
    protected static $defaultName = 'app:task:list';
    private $repository;

    public function __construct(TaskRepository $repository)
    {
        parent::__construct();
        $this->repository = $repository;
    }

    protected function configure()
    {
        $this->addOption('number', null, InputOption::VALUE_REQUIRED, 'The issue number we are interested in', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var int|null $number */
        $number = $input->getOption('number');
        if (null === $number) {
            $criteria = [];
        } else {
            $criteria = ['number' => $number];
        }

        $limit = 100;
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

        $io = new SymfonyStyle($input, $output);
        $io->table(['Repo', 'Number', 'Action', 'Verify After'], $rows);
        $io->write(sprintf('Total of %d items in the table. ', $taskCount = count($tasks)));
        if ($limit === $taskCount) {
            $io->write('There might be more tasks in the database.');
        }
        $io->newLine();

        return 0;
    }

    private function actionToString(int $action): string
    {
        $data = [
            Task::ACTION_CLOSE_STALE => 'Close stale',
            Task::ACTION_CLOSE_DRAFT => 'Close draft',
            Task::ACTION_INFORM_CLOSE_STALE => 'Inform about close',
            Task::ACTION_SUGGEST_REVIEWER => 'Suggest reviewer',
        ];

        return $data[$action] ?? 'Unknown action';
    }
}
