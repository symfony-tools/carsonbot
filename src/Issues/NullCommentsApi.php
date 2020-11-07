<?php

namespace App\Issues;

use App\Model\Repository;

class NullCommentsApi implements CommentsApiInterface
{
    public function commentOnIssue(Repository $repository, $issueNumber, $commentBody)
    {
    }
}
