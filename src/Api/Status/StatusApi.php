<?php

namespace App\Api\Status;

use App\Model\Repository;

/**
 * Sets and retrieves the status for an issue.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface StatusApi
{
    public function getIssueStatus(int $issueNumber, Repository $repository): ?string;

    public function setIssueStatus(int $issueNumber, ?string $newStatus, Repository $repository): void;
}
