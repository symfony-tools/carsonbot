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
    public function commentOnIssue(Repository $repository, $issueNumber, $commentBody);
}
