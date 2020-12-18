<?php

namespace App\Command;

use App\Api\Issue\IssueApi;
use App\Api\Issue\IssueType;
use App\Api\Label\LabelApi;
use App\Entity\Task;
use App\Service\RepositoryProvider;
use App\Service\StaleIssueCommentGenerator;
use App\Service\TaskScheduler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Close issues not been updated in a long while.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class PingStaleIssuesCommand extends Command
{
    public const STALE_IF_NOT_UPDATED_SINCE = '-12months';
    public const MESSAGE_TWO_AFTER = '+2weeks';
    public const MESSAGE_THREE_AND_CLOSE_AFTER = '+2weeks';

    protected static $defaultName = 'app:issue:ping-stale';

    private $repositoryProvider;
    private $issueApi;
    private $scheduler;
    private $commentGenerator;
    private $labelApi;

    public function __construct(RepositoryProvider $repositoryProvider, IssueApi $issueApi, TaskScheduler $scheduler, StaleIssueCommentGenerator $commentGenerator, LabelApi $labelApi)
    {
        parent::__construct();
        $this->repositoryProvider = $repositoryProvider;
        $this->issueApi = $issueApi;
        $this->scheduler = $scheduler;
        $this->commentGenerator = $commentGenerator;
        $this->labelApi = $labelApi;
    }

    protected function configure()
    {
        $this->addArgument('repository', InputArgument::REQUIRED, 'The full name to the repository, eg symfony/symfony-docs');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do a test search without making any comments or changes');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var string $repositoryName */
        $repositoryName = $input->getArgument('repository');
        $repository = $this->repositoryProvider->getRepository($repositoryName);
        if (null === $repository) {
            $output->writeln('Repository not configured');

            return 1;
        }

        $notUpdatedAfter = new \DateTimeImmutable(self::STALE_IF_NOT_UPDATED_SINCE);
        $issues = $this->issueApi->findStaleIssues($repository, $notUpdatedAfter);

        if ($input->getOption('dry-run')) {
            foreach ($issues as $issue) {
                $output->writeln(sprintf('Marking issue #%s as "Stalled". Link https://github.com/%s/issues/%s', $issue['number'], $repository->getFullName(), $issue['number']));
            }

            return 0;
        }

        foreach ($issues as $issue) {
            $comment = $this->commentGenerator->getComment($this->extractType($issue));
            $this->issueApi->commentOnIssue($repository, $issue['number'], $comment);
            $this->labelApi->addIssueLabel($issue['number'], 'Stalled', $repository);

            // add a scheduled task to process this issue again after 2 weeks
            $this->scheduler->runLater($repository, $issue['number'], Task::ACTION_INFORM_CLOSE_STALE, new \DateTimeImmutable(self::MESSAGE_TWO_AFTER));
        }

        return 0;
    }

    /**
     * Extract type from issue array. Make sure we priorities labels if there are
     * more than one type defined.
     */
    private function extractType(array $issue)
    {
        $types = [
            IssueType::FEATURE => false,
            IssueType::BUG => false,
            IssueType::RFC => false,
        ];

        foreach ($issue['labels'] as $label) {
            if (isset($types[$label['name']])) {
                $types[$label['name']] = true;
            }
        }

        foreach ($types as $type => $exists) {
            if ($exists) {
                return $type;
            }
        }

        return IssueType::UNKNOWN;
    }
}
