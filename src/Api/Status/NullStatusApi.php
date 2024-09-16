<?php

namespace App\Api\Status;

use App\Model\Repository;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class NullStatusApi implements StatusApi
{
    public function getIssueStatus(int $issueNumber, Repository $repository): ?string
    {
        return null;
    }

    public function setIssueStatus(int $issueNumber, ?string $newStatus, Repository $repository): void
    {
    }
}
