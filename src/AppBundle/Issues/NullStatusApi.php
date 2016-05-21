<?php

namespace AppBundle\Issues;

use AppBundle\Repository\Repository;

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
