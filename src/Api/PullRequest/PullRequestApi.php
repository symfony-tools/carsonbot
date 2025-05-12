<?php

declare(strict_types=1);

namespace App\Api\PullRequest;

use App\Model\Repository;

/**
 * Actions that are specific to pull requests.
 * Actions like comment/close can be performed using the IssueApi.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
interface PullRequestApi
{
    /**
     * @return array<string, mixed>
     */
    public function show(Repository $repository, int $number): array;

    public function updateTitle(Repository $repository, int $number, string $title, ?string $body = null): void;

    public function getAuthorCount(Repository $repository, string $author): int;
}
