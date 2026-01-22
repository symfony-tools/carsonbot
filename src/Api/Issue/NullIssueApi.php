<?php

namespace App\Api\Issue;

use App\Model\Repository;

class NullIssueApi implements IssueApi
{
    public function open(Repository $repository, string $title, string $body, array $labels): void
    {
    }

    public function show(Repository $repository, int $issueNumber): array
    {
        return [];
    }

    public function commentOnIssue(Repository $repository, int $issueNumber, string $commentBody): void
    {
    }

    public function lastCommentWasMadeByBot(Repository $repository, int $number): bool
    {
        return false;
    }

    public function findStaleIssues(Repository $repository, \DateTimeImmutable $noUpdateAfter): iterable
    {
        return [];
    }

    public function close(Repository $repository, int $issueNumber): void
    {
    }

    public function findBotComment(Repository $repository, int $issueNumber, string $search): ?int
    {
        return null;
    }

    public function removeComment(Repository $repository, int $commentId): void
    {
    }
}
