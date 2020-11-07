<?php

namespace App\Issues;

use App\Model\Repository;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class NullStatusApi implements StatusApi
{
    public function getIssueStatus($issueNumber, Repository $repository)
    {
    }

    public function setIssueStatus($issueNumber, $newStatus, Repository $repository)
    {
    }
}
