<?php

namespace App\Api\Issue;

use App\Model\Repository;

class NullIssueApi implements IssueApi
{
    public function open(Repository $repository, string $title, string $body, array $labels)
    {
    }

    public function show(Repository $repository, $issueNumber): array
    {
        return [];
    }

    public function commentOnIssue(Repository $repository, $issueNumber, string $commentBody)
    {
    }

    public function lastCommentWasMadeByBot(Repository $repository, $number): bool
    {
        return false;
    }

    public function findStaleIssues(Repository $repository, \DateTimeImmutable $noUpdateAfter): iterable
    {
        return [];
    }

    public function close(Repository $repository, $issueNumber, ?string $reason = null): void
    {
    }
}
