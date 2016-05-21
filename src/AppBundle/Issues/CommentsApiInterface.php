<?php

namespace AppBundle\Issues;

use AppBundle\Repository\Repository;

interface CommentsApiInterface
{
    public function commentOnIssue($issueNumber, $commentBody, Repository $repository);
}
