<?php

namespace AppBundle\Issues;

use AppBundle\Repository\Repository;

class NullCommentsApi implements CommentsApiInterface
{
    public function commentOnIssue($issueNumber, $commentBody, Repository $repository)
    {
    }
}
