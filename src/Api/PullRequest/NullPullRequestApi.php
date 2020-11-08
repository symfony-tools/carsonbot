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

    public function findReviewer(Repository $repository, $number, string $type)
    {
    }
}
