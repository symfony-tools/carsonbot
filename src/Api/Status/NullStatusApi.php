<?php

namespace App\Api\Status;

use App\Model\Repository;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class NullStatusApi implements StatusApi
{
    public function getIssueStatus($issueNumber, Repository $repository): ?string
    {
        return null;
    }

    public function setIssueStatus($issueNumber, ?string $newStatus, Repository $repository)
    {
    }
}
