<?php

namespace App\Api\Issue;

use App\Model\Repository;

/**
 * Create, update, close or comment on issues. Not that "issue" also refers to a
 * pull request.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
interface IssueApi
{
    /**
     * Open new issue or update existing issue.
     */
    public function open(Repository $repository, string $title, string $body, array $labels);

    public function show(Repository $repository, $issueNumber): array;

    public function commentOnIssue(Repository $repository, $issueNumber, string $commentBody);

    /**
     * Close an issue or a pull request.
     */
    public function close(Repository $repository, $issueNumber);
}
