<?php

namespace App\Api\Issue;

use App\Model\Repository;
use Github\Api\Issue\Comments;

class GithubIssueApi implements IssueApi
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
