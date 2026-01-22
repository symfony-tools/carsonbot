<?php

namespace App\Api\Issue;

use App\Model\Repository;

/**
 * Create, update, close or comment on issues. Note that "issue" also refers to a
 * pull request.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
interface IssueApi
{
    /**
     * Open new issue or update existing issue.
     *
     * @param array<string> $labels
     */
    public function open(Repository $repository, string $title, string $body, array $labels): void;

    /**
     * @return array<string, mixed>
     */
    public function show(Repository $repository, int $issueNumber): array;

    public function commentOnIssue(Repository $repository, int $issueNumber, string $commentBody): void;

    public function lastCommentWasMadeByBot(Repository $repository, int $number): bool;

    /**
     * @return iterable<array<string, mixed>>
     */
    public function findStaleIssues(Repository $repository, \DateTimeImmutable $noUpdateAfter): iterable;

    /**
     * Close an issue and mark it as "not_planned".
     */
    public function close(Repository $repository, int $issueNumber): void;

    public function findBotComment(Repository $repository, int $issueNumber, string $search): ?int;

    public function removeComment(Repository $repository, int $commentId): void;
}
