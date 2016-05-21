<?php

namespace AppBundle\Issues\GitHub;

use AppBundle\Issues\CommentsApiInterface;
use AppBundle\Repository\Repository;

class CommentsApi implements CommentsApiInterface
{
    public function commentOnIssue($issueNumber, Repository $repository)
    {
        // todo - actually comment!
    }
}