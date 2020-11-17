<?php

namespace App\Subscriber;

use App\Api\Issue\IssueApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Service\ComplementGenerator;
use App\Service\SymfonyVersionProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class UnsupportedBranchSubscriber implements EventSubscriberInterface
{
    private $symfonyVersionProvider;
    private $issueApi;
    private $complementGenerator;
    private $logger;

    public function __construct(SymfonyVersionProvider $symfonyVersionProvider, IssueApi $issueApi, ComplementGenerator $complementGenerator, LoggerInterface $logger)
    {
        $this->symfonyVersionProvider = $symfonyVersionProvider;

        $this->issueApi = $issueApi;
        $this->complementGenerator = $complementGenerator;
        $this->logger = $logger;
    }

    /**
     * Sets milestone on PRs that target non-default branch.
     */
    public function onPullRequest(GitHubEvent $event)
    {
        $data = $event->getData();
        if (!in_array($data['action'], ['opened', 'ready_for_review']) || ($data['pull_request']['draft'] ?? false)) {
            return;
        }

        $targetBranch = $data['pull_request']['base']['ref'];
        if ($targetBranch === $data['repository']['default_branch']) {
            return;
        }

        try {
            $validBranches = $this->symfonyVersionProvider->getSupportedVersions();
        } catch (\Throwable $e) {
            $this->logger->error('Failed to get valid branches', ['exception' => $e]);

            return;
        }

        if (in_array($targetBranch, $validBranches)) {
            return;
        }

        $number = $data['pull_request']['number'];
        $complement = $this->complementGenerator->getPullRequestComplement();
        $validBranchesString = implode(', ', $validBranches);
        $this->issueApi->commentOnIssue($event->getRepository(), $number, <<<TXT
Hey!

$complement

But you have made this PR towards a branch that is not maintained anymore. :/
Could you update the [PR base branch](https://docs.github.com/en/free-pro-team@latest/github/collaborating-with-issues-and-pull-requests/changing-the-base-branch-of-a-pull-request) to target one of these branches instead? $validBranchesString.

Cheers!

Carsonbot
TXT
);

        $event->setResponseData([
            'pull_request' => $number,
            'unsupported_branch' => $targetBranch,
        ]);
    }

    public static function getSubscribedEvents()
    {
        return [
            GitHubEvents::PULL_REQUEST => 'onPullRequest',
        ];
    }
}
