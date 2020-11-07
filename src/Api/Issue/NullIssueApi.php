<?php

namespace App\Api\Issue;

use App\Model\Repository;

class NullIssueApi implements IssueApi
{
    public function commentOnIssue(Repository $repository, $issueNumber, $commentBody)
    {
    }
}
