<?php

namespace App\Issues\GitHub;

use App\Issues\CommentsApiInterface;
use App\Model\Repository;
use Github\Api\Issue\Comments;

class GithubCommentsApi implements CommentsApiInterface
{
    private $issueCommentApi;

    public function __construct(Comments $issueCommentApi)
    {
        $this->issueCommentApi = $issueCommentApi;
    }

    /**
     * This will comment on both Issues and Pull Requests.
     */
    public function commentOnIssue(Repository $repository, $issueNumber, $commentBody)
    {
        $this->issueCommentApi->create(
            $repository->getVendor(),
            $repository->getName(),
            $issueNumber,
            ['body' => $commentBody]
        );
    }
}
