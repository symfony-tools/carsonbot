<?php

namespace App\Issues;

use App\Repository\Repository;

class NullCommentsApi implements CommentsApiInterface
{
    public function commentOnIssue(Repository $repository, $issueNumber, $commentBody)
    {
    }
}
