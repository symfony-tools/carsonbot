<?php

namespace App\Issues;

use App\Repository\Repository;

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
