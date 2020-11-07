<?php

namespace App\Api\Issue;

use App\Model\Repository;

class NullCommentsApi implements CommentsApiInterface
{
    public function commentOnIssue(Repository $repository, $issueNumber, $commentBody)
    {
    }
}
