<?php

namespace App\Api\Issue;

use App\Model\Repository;

interface CommentsApiInterface
{
    public function commentOnIssue(Repository $repository, $issueNumber, $commentBody);
}
