<?php

namespace App\Api\Issue;

use App\Model\Repository;
use Github\Api\Issue;
use Github\Api\Issue\Comments;
use Github\Api\Issue\Timeline;
use Github\Api\PullRequest\Review;
use Github\Api\Search;
use Github\Exception\RuntimeException;
use Github\ResultPager;

class GithubIssueApi implements IssueApi
{
    private $resultPager;
    private $issueCommentApi;
    private $reviewApi;
    private $issueApi;
    private $searchApi;
    private $timelineApi;
    private $botUsername;

    public function __construct(ResultPager $resultPager, Comments $issueCommentApi, Review $reviewApi, Issue $issueApi, Search $searchApi, Timeline $timelineApi, string $botUsername)
    {
        $this->resultPager = $resultPager;
        $this->issueCommentApi = $issueCommentApi;
        $this->reviewApi = $reviewApi;
        $this->issueApi = $issueApi;
        $this->searchApi = $searchApi;
        $this->timelineApi = $timelineApi;
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

    /**
     * Has this PR or issue comments/reviews from others than the author?
     */
    public function hasActivity(Repository $repository, $number): bool
    {
        $issue = $this->issueApi->show($repository->getVendor(), $repository->getName(), $number);
        $author = $issue['user']['login'] ?? null;

        try {
            $reviewComments = $this->reviewApi->all($repository->getVendor(), $repository->getName(), $number);
        } catch (RuntimeException $e) {
            // This was not a PR =)
            $reviewComments = [];
        }

        $all = array_merge($reviewComments, $this->issueCommentApi->all($repository->getVendor(), $repository->getName(), $number, ['per_page' => 100]));
        foreach ($all as $comment) {
            if (!in_array($comment['user']['login'], [$author, $this->botUsername])) {
                return true;
            }
        }

        return false;
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

    public function findStaleIssues(Repository $repository, \DateTimeImmutable $noUpdateAfter): iterable
    {
        return $this->resultPager->fetchAllLazy($this->searchApi, 'issues', [sprintf('repo:%s is:issue -label:"Keep open" is:open updated:<%s', $repository->getFullName(), $noUpdateAfter->format('Y-m-d')), 'updated', 'desc']);
    }

    public function getUsers(Repository $repository, $issueNumber): array
    {
        $timeline = $this->timelineApi->all($repository->getVendor(), $repository->getName(), $issueNumber);
        $users = [];
        foreach ($timeline as $event) {
            $users[] = $event['actor']['login'] ?? $event['user']['login'] ?? $event['author']['email'] ?? '';
            if (isset($event['body'])) {
                // Parse body for user reference
                if (preg_match_all('|@([a-zA-z_\-0-9]+)|', $event['body'], $matches)) {
                    foreach ($matches[1] as $match) {
                        $users[] = $match;
                    }
                }
            }
        }

        return array_map(function ($a) { return strtolower($a); }, array_unique($users));
    }
}
