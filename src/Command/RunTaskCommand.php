<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\TaskRepository;
use App\Service\TaskRunner;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:task:run', description: 'Run scheduled tasks')]
final class RunTaskCommand
{
    public function __construct(
        private readonly TaskRepository $repository,
        private readonly TaskRunner $taskRunner,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(
        OutputInterface $output,
        #[InputOption(name: 'limit', shortcut: 'l', description: 'Limit the number of tasks to run')]
        int $limit = 10,
    ): int {
        foreach ($this->repository->getTasksToVerify($limit) as $task) {
            try {
                $this->taskRunner->run($task);
            } catch (\Exception $e) {
                $this->logger->error('Failed running task', ['exception' => $e]);
                $output->writeln($e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}
