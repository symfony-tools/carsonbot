<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\TaskRepository;
use App\Service\TaskRunner;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class RunTaskCommand extends Command
{
    protected static $defaultName = 'app:task:run';

    public function __construct(
        private readonly TaskRepository $repository,
        private readonly TaskRunner $taskRunner,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Limit the number of tasks to run', '10');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = (int) $input->getOption('limit');
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
