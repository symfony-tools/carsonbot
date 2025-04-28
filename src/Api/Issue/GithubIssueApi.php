<?php

namespace App\Api\Issue;

use App\Model\Repository;
use App\Service\TaskHandler\CloseDraftHandler;
use App\Service\TaskHandler\CloseStaleIssuesHandler;
use Github\Api\Issue;
use Github\Api\Issue\Comments;
use Github\Api\Search;
use Github\ResultPager;

class GithubIssueApi implements IssueApi
{
    public function __construct(
        private readonly ResultPager $resultPager,
        private readonly Comments $issueCommentApi,
        private readonly Issue $issueApi,
        private readonly Search $searchApi,
        private readonly string $botUsername,
    ) {
    }

    public function open(Repository $repository, string $title, string $body, array $labels)
    {
        $params = [
            'title' => $title,
            'labels' => $labels,
            'body' => $body,
        ];

        $issueNumber = null;
        $existingIssues = $this->resultPager->fetchAllLazy($this->searchApi, 'issues', [sprintf('repo:%s "%s" is:open author:%s', $repository->getFullName(), $title, $this->botUsername), 'updated', 'desc']);
        foreach ($existingIssues as $issue) {
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
        $allComments = $this->issueCommentApi->all($repository->getVendor(), $repository->getName(), $number, ['per_page' => 100]);
        $lastComment = $allComments[count($allComments) - 1] ?? [];

        return $this->botUsername === ($lastComment['user']['login'] ?? null);
    }

    public function show(Repository $repository, $issueNumber): array
    {
        return $this->issueApi->show($repository->getVendor(), $repository->getName(), $issueNumber);
    }

    /**
     * Close an issue and mark it as "not_planned".
     *
     * @see CloseDraftHandler
     * @see CloseStaleIssuesHandler
     */
    public function close(Repository $repository, $issueNumber): void
    {
        $this->issueApi->update(
            $repository->getVendor(),
            $repository->getName(),
            $issueNumber,
            [
                'state' => 'closed',
                'state_reason' => 'not_planned',
            ],
        );
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

    public function findStaleIssues(Repository $repository, \DateTimeImmutable $noUpdateAfter): iterable
    {
        return $this->resultPager->fetchAllLazy($this->searchApi, 'issues', [
            sprintf('repo:%s is:issue -label:"Keep open" -label:"Missing translations" is:open -linked:pr updated:<%s', $repository->getFullName(), $noUpdateAfter->format('Y-m-d')),
            'updated',
            'desc',
        ]);
    }
}
