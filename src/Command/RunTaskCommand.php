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

class RunTaskCommand extends Command
{
    protected static $defaultName = 'app:task:run';
    private $repository;
    private $taskRunner;
    private $logger;

    public function __construct(TaskRepository $repository, TaskRunner $taskRunner, LoggerInterface $logger)
    {
        parent::__construct();
        $this->repository = $repository;
        $this->taskRunner = $taskRunner;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Limit the number of tasks to run', '10');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var int $limit */
        $limit = $input->getOption('limit');
        foreach ($this->repository->getTasksToVerify($limit) as $task) {
            try {
                $this->taskRunner->run($task);
            } catch (\Exception $e) {
                $this->logger->error('Failed running task', ['exception' => $e]);
                $output->writeln($e->getMessage());
            }
        }

        return 0;
    }
}
