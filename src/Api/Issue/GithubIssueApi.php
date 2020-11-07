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
        $existingIssues = $this->searchApi->issues(sprintf('repo:%s "%s" is:open author:%s', $repository->getFullName(), $title, $this->botUsername));
        foreach ($existingIssues['items'] ?? [] as $issue) {
            $issueNumber = $issue['number'];
        }

        if (null === $issueNumber) {
            $this->issueApi->create($repository->getVendor(), $repository->getName(), $params);
        } else {
            unset($params['labels']);
            $this->issueApi->update($repository->getVendor(), $repository->getName(), $issueNumber, $params);
        }
    }

    public function lastCommentWasMadeByBot(Repository $repository, $number): bool
    {
        $allComments = $this->issueCommentApi->all($repository->getVendor(), $repository->getName(), $number);
        $lastComment = $allComments[count($allComments) - 1] ?? [];

        return $this->botUsername === ($lastComment['user']['login'] ?? null);
    }

    public function show(Repository $repository, $issueNumber): array
    {
        return $this->issueApi->show($repository->getVendor(), $repository->getName(), $issueNumber);
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

    public function findStaleIssues(Repository $repository, \DateTimeImmutable $noUpdateAfter): array
    {
        $issues = $this->searchApi->issues(sprintf('repo:%s is:issue -linked:pr -label:"Keep open" is:open updated:<%s', $repository->getFullName(), $noUpdateAfter->format('Y-m-d')));

        return $issues['items'] ?? [];
    }
}
