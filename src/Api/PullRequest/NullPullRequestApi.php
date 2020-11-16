<?php

declare(strict_types=1);

namespace App\Api\PullRequest;

use App\Model\Repository;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class NullPullRequestApi implements PullRequestApi
{
    public function show(Repository $repository, $number): array
    {
        return [];
    }

    public function updateTitle(Repository $repository, $number, $title): void
    {
    }

    public function findReviewer(Repository $repository, $number, string $type)
    {
    }

    public function getAuthorCount(Repository $repository, string $author): int
    {
        return 1;
    }
}
