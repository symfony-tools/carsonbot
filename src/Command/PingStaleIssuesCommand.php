<?php

declare(strict_types=1);

namespace App\Command;

use App\Api\Issue\IssueApi;
use App\Api\Issue\IssueType;
use App\Api\Label\LabelApi;
use App\Entity\Task;
use App\Service\RepositoryProvider;
use App\Service\StaleIssueCommentGenerator;
use App\Service\TaskScheduler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Close issues not been updated in a long while.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
#[AsCommand(name: 'app:issue:ping-stale', description: 'Ping stale issues and schedule them for closing')]
final class PingStaleIssuesCommand
{
    public const string MESSAGE_TWO_AFTER = '+2weeks';
    public const string MESSAGE_THREE_AND_CLOSE_AFTER = '+2weeks';

    public function __construct(
        private readonly RepositoryProvider $repositoryProvider,
        private readonly IssueApi $issueApi,
        private readonly TaskScheduler $scheduler,
        private readonly StaleIssueCommentGenerator $commentGenerator,
        private readonly LabelApi $labelApi,
    ) {
    }

    public function __invoke(
        OutputInterface $output,
        #[InputArgument(description: 'The full name to the repository, eg symfony/symfony-docs')]
        string $repository,
        #[InputOption(description: 'A string representing a time period to for how long the issue has been stalled.')]
        string $notUpdatedFor = '12months',
        #[InputOption(description: 'Do a test search without making any comments or changes')]
        bool $dryRun = false,
    ): int {
        $repo = $this->repositoryProvider->getRepository($repository);
        if (null === $repo) {
            $output->writeln('Repository not configured');

            return Command::FAILURE;
        }

        $notUpdatedAfter = new \DateTimeImmutable('-'.ltrim($notUpdatedFor, '-'));
        $issues = $this->issueApi->findStaleIssues($repo, $notUpdatedAfter);

        if ($dryRun) {
            foreach ($issues as $issue) {
                $output->writeln(sprintf('Marking issue #%s as "Stalled". Link https://github.com/%s/issues/%s', $issue['number'], $repo->getFullName(), $issue['number']));
            }

            return Command::SUCCESS;
        }

        foreach ($issues as $issue) {
            /**
             * @var array{number: int, name: string, labels: array<int, array{name: string}>} $issue
             */
            $comment = $this->commentGenerator->getComment($this->extractType($issue));
            $this->issueApi->commentOnIssue($repo, $issue['number'], $comment);
            $this->labelApi->addIssueLabel($issue['number'], 'Stalled', $repo);

            // add a scheduled task to process this issue again after 2 weeks
            $this->scheduler->runLater($repo, $issue['number'], Task::ACTION_INFORM_CLOSE_STALE, new \DateTimeImmutable(self::MESSAGE_TWO_AFTER));
        }

        return Command::SUCCESS;
    }

    /**
     * Extract type from issue array. Make sure we prioritize labels if there are
     * more than one type defined.
     *
     * @param array{number: int, name: string, labels: array<int, array{name: string}>} $issue
     */
    private function extractType(array $issue): string
    {
        $types = [
            IssueType::FEATURE => false,
            IssueType::BUG => false,
            IssueType::RFC => false,
            IssueType::DOCUMENTATION => false,
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
