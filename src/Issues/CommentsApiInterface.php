<?php

namespace App\Issues;

use App\Repository\Repository;

interface CommentsApiInterface
{
    public function commentOnIssue(Repository $repository, $issueNumber, $commentBody);
}
