<?php

namespace App\Api\Issue;

use App\Model\Repository;
use Github\Api\Issue;
use Github\Api\Issue\Comments;
use Github\Api\Search;

class GithubIssueApi implements IssueApi
{
    private $issueCommentApi;
    private $botUsername;
    private $issueApi;
    private $searchApi;

    public function __construct(Comments $issueCommentApi, Issue $issueApi, Search $searchApi, string $botUsername)
    {
        $this->issueCommentApi = $issueCommentApi;
        $this->issueApi = $issueApi;
        $this->searchApi = $searchApi;
        $this->botUsername = $botUsername;
    }

    public function open(Repository $repository, string $title, string $body, array $labels)
    {
        $params = [
            'title' => $title,
            'labels' => $labels,
            'body' => $body,
        ];

        $issueNumber = null;
        $exitingIssues = $this->searchApi->issues(sprintf('repo:%s "%s" is:open author:%s', $repository->getFullName(), $title, $this->botUsername));
        foreach ($exitingIssues['items'] ?? [] as $issue) {
            $issueNumber = $issue['number'];
        }

        if (null === $issueNumber) {
            $this->issueApi->create($repository->getVendor(), $repository->getName(), $params);
        } else {
            unset($params['labels']);
            $this->issueApi->update($repository->getVendor(), $repository->getName(), $issueNumber, $params);
        }
    }

    public function close(Repository $repository, $issueNumber)
    {
        $this->issueApi->update($repository->getVendor(), $repository->getName(), $issueNumber, ['state' => 'closed']);
    }

    /**
     * This will comment on both Issues and Pull Requests.
     */
    public function commentOnIssue(Repository $repository, $issueNumber, string $commentBody)
    {
        $this->issueCommentApi->create(
            $repository->getVendor(),
            $repository->getName(),
            $issueNumber,
            ['body' => $commentBody]
        );
    }
}
