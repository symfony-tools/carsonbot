<?php

namespace AppBundle\Issues\GitHub;

use AppBundle\Issues\CommentsApiInterface;
use AppBundle\Repository\Repository;
use Github\Api\Issue\Comments;

class GithubCommentsApi implements CommentsApiInterface
{
    private $commentsApi;

    public function __construct(Comments $commentsApi)
    {
        $this->commentsApi = $commentsApi;
    }

    public function commentOnIssue($issueNumber, $commentBody, Repository $repository)
    {
        $this->commentsApi->create(
            $repository->getVendor(),
            $repository->getName(),
            $issueNumber,
            ['body' => $commentBody]
        );
    }
}
