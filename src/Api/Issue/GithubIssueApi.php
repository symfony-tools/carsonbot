<?php

namespace App\Api\Issue;

use App\Model\Repository;
use Github\Api\GraphQL;
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
        private readonly GraphQL $graphqlApi,
        private readonly string $botUsername,
    ) {
    }

    public function open(Repository $repository, string $title, string $body, array $labels): void
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

    public function lastCommentWasMadeByBot(Repository $repository, int $number): bool
    {
        $allComments = $this->issueCommentApi->all($repository->getVendor(), $repository->getName(), $number, ['per_page' => 100]);
        $lastComment = $allComments[count($allComments) - 1] ?? [];

        return $this->botUsername === ($lastComment['user']['login'] ?? null);
    }

    public function show(Repository $repository, int $issueNumber): array
    {
        return $this->issueApi->show($repository->getVendor(), $repository->getName(), $issueNumber);
    }

    public function close(Repository $repository, int $issueNumber): void
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
    public function commentOnIssue(Repository $repository, int $issueNumber, string $commentBody): void
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

    public function findBotComment(Repository $repository, int $issueNumber, string $search): ?string
    {
        $result = $this->graphqlApi->execute('
            query($owner: String!, $repo: String!, $number: Int!) {
                repository(owner: $owner, name: $repo) {
                    issueOrPullRequest(number: $number) {
                        ... on Issue { comments(last: 100) { ...botCommentNodes } }
                        ... on PullRequest { comments(last: 100) { ...botCommentNodes } }
                    }
                }
            }
            fragment botCommentNodes on IssueCommentConnection {
                nodes { id body isMinimized author { login } }
            }
        ', [
            'owner' => $repository->getVendor(),
            'repo' => $repository->getName(),
            'number' => $issueNumber,
        ]);

        $nodes = $result['data']['repository']['issueOrPullRequest']['comments']['nodes'] ?? [];
        foreach (array_reverse($nodes) as $comment) {
            if ($comment['isMinimized'] ?? false) {
                continue;
            }

            if ($this->botUsername !== ($comment['author']['login'] ?? null)) {
                continue;
            }

            if (str_contains($comment['body'] ?? '', $search)) {
                return $comment['id'];
            }
        }

        return null;
    }

    public function minimizeComment(string $commentNodeId): void
    {
        $this->graphqlApi->execute('
            mutation($subjectId: ID!) {
                minimizeComment(input: {subjectId: $subjectId, classifier: OUTDATED}) {
                    minimizedComment { isMinimized }
                }
            }
        ', ['subjectId' => $commentNodeId]);
    }
}
