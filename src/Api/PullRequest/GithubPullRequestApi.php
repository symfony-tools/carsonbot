<?php

namespace App\Api\PullRequest;

use App\Model\Repository;
use Github\Api\PullRequest;
use Github\Api\Repo;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class GithubPullRequestApi implements PullRequestApi
{
    /**
     * @var Repo
     */
    private $github;
    private $botUsername;
    private $pullRequest;

    public function __construct(Repo $github, PullRequest $pullRequest, string $botUsername)
    {
        $this->github = $github;
        $this->botUsername = $botUsername;
        $this->pullRequest = $pullRequest;
    }

    public function show(Repository $repository, $number): array
    {
        return (array) $this->pullRequest->show($repository->getVendor(), $repository->getName(), $number);
    }

    /**
     * Trigger start of a "find reviewer" job. The job runs on github actions and will comment on the PR.
     */
    public function findReviewer(Repository $repository, $number, string $type)
    {
        $this->github->dispatch($this->botUsername, 'carsonbot', 'find-reviewer', [
            'repository' => $repository->getFullName(),
            'pull_request_number' => $number,
            'type' => $type,
        ]);
    }
}
