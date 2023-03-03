<?php

namespace App\Api\PullRequest;

use App\Model\Repository;
use Github\Api\PullRequest;
use Github\Api\Repo;
use Github\Api\Search;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class GithubPullRequestApi implements PullRequestApi
{
    private $github;
    private $pullRequest;
    private $search;
    private $botUsername;

    public function __construct(Repo $github, PullRequest $pullRequest, Search $search, string $botUsername)
    {
        $this->github = $github;
        $this->pullRequest = $pullRequest;
        $this->search = $search;
        $this->botUsername = $botUsername;
    }

    public function show(Repository $repository, $number): array
    {
        return (array) $this->pullRequest->show($repository->getVendor(), $repository->getName(), $number);
    }

    public function updateTitle(Repository $repository, $number, string $title, string $body = null): void
    {
        $params = ['title' => $title];

        if (null !== $body) {
            $params['body'] = $body;
        }

        $this->pullRequest->update($repository->getVendor(), $repository->getName(), $number, $params);
    }

    public function getAuthorCount(Repository $repository, string $author): int
    {
        $result = $this->search->issues(sprintf('is:pr repo:%s author:%s', $repository->getFullName(), $author));

        return $result['total_count'];
    }
}
