<?php

declare(strict_types=1);

namespace App\Command;

use App\Api\Issue\IssueApi;
use App\Service\RepositoryProvider;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Open or update issues.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
#[AsCommand(
    name: 'app:issue:open',
    description: 'Open an issue, or update the one the bot already opened with the same title',
)]
final class OpenIssueCommand
{
    public function __construct(
        private readonly RepositoryProvider $repositoryProvider,
        private readonly IssueApi $issueApi,
    ) {
    }

    public function __invoke(
        OutputInterface $output,
        #[Argument(description: 'The full name to the repository, eg symfony/symfony-docs')]
        string $repository,
        #[Argument(description: 'The title of the issue')]
        string $title,
        #[Argument(description: 'The path to the issue body text file')]
        string $file,
    ): int {
        $repo = $this->repositoryProvider->getRepository($repository);
        if (null === $repo) {
            $output->writeln('Repository not configured');

            return Command::FAILURE;
        }

        $body = file_get_contents($file);
        if (false === $body) {
            return Command::FAILURE;
        }

        $this->issueApi->open($repo, $title, $body, ['help wanted']);

        return Command::SUCCESS;
    }
}
