<?php

namespace App\Api\PullRequest;

use App\Model\Repository;
use Github\Api\PullRequest;
use Github\Api\Search;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class GithubPullRequestApi implements PullRequestApi
{
    public function __construct(
        private readonly PullRequest $pullRequest,
        private readonly Search $search,
    ) {
    }

    public function show(Repository $repository, int $number): array
    {
        return (array) $this->pullRequest->show($repository->getVendor(), $repository->getName(), $number);
    }

    public function updateTitle(Repository $repository, int $number, string $title, ?string $body = null): void
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

    public function getFiles(Repository $repository, int $number): array
    {
        $files = $this->pullRequest->files($repository->getVendor(), $repository->getName(), $number);

        /** @var list<string> $result */
        $result = array_values(array_map(fn (array $file): string => $file['filename'], $files));

        return $result;
    }
}
