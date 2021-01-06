<?php

namespace App\Command;

use App\Api\Issue\IssueApi;
use App\Service\ComplementGenerator;
use App\Service\RepositoryProvider;
use App\Service\ReviewerFilter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Write a comment and suggest reviewer.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class SuggestReviewerCommand extends Command
{
    public const TYPE_SUGGEST = 'suggest';
    public const TYPE_DEMAND = 'demand';
    protected static $defaultName = 'app:review:suggest';
    private $issueApi;
    private $repositoryProvider;
    private $reviewerFilter;
    private $complementGenerator;

    public function __construct(RepositoryProvider $repositoryProvider, IssueApi $issueApi, ReviewerFilter $reviewerFilter, ComplementGenerator $complementGenerator)
    {
        parent::__construct();
        $this->issueApi = $issueApi;
        $this->repositoryProvider = $repositoryProvider;
        $this->reviewerFilter = $reviewerFilter;
        $this->complementGenerator = $complementGenerator;
    }

    protected function configure()
    {
        $this->addArgument('repository', InputArgument::REQUIRED, 'The full name to the repository, eg symfony/symfony-docs');
        $this->addArgument('number', InputArgument::REQUIRED, 'Pull request number');
        $this->addArgument('type', InputArgument::REQUIRED, 'Type is either "suggest" or "demand".');
        $this->addArgument('contributor_json', InputArgument::REQUIRED, 'The path to the issue body text file');
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

        /** @var string $pullRequestNumber */
        $pullRequestNumber = $input->getArgument('number');
        /** @var string $type */
        $type = $input->getArgument('type');
        if (self::TYPE_DEMAND !== $type && self::TYPE_SUGGEST !== $type) {
            $output->writeln(sprintf('Invalid type. You provided: "%s"', $type));

            return 1;
        }

        /** @var string $path */
        $path = $input->getArgument('contributor_json');
        $json = file_get_contents($path);
        if (false === $json) {
            return 1;
        }

        $contributors = json_decode($json, true);
        $reviewer = $this->reviewerFilter->suggestReviewer($contributors, $repository, $pullRequestNumber);
        if (null === $reviewer) {
            if (self::TYPE_SUGGEST === $type) {
                $output->writeln('We could not find any reviewer.');

                return 0;
            }

            $this->issueApi->commentOnIssue($repository, $pullRequestNumber, 'I\'m sorry. I could not find any suitable reviewer.');

            return 0;
        }

        if (self::TYPE_DEMAND === $type) {
            $this->issueApi->commentOnIssue($repository, $pullRequestNumber, sprintf('@%s could maybe review this PR?', $reviewer));

            return 0;
        }

        $complement = $this->complementGenerator->getPullRequestComplement();
        $this->issueApi->commentOnIssue($repository, $pullRequestNumber, <<<TXT
Hey!

$complement

I think @$reviewer has recently worked with this code. Maybe they can help review this?

Cheers!

Carsonbot
TXT
);

        return 0;
    }
}
