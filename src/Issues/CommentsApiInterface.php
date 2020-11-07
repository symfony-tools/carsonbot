<?php

namespace App\Issues;

use App\Model\Repository;

interface CommentsApiInterface
{
    public function commentOnIssue(Repository $repository, $issueNumber, $commentBody);
}
