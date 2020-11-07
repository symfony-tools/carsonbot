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
    public function getIssueStatus($issueNumber, Repository $repository): ?string;

    public function setIssueStatus($issueNumber, ?string $newStatus, Repository $repository);
}
