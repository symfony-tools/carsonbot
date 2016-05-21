<?php

namespace AppBundle\Issues;

use AppBundle\Repository\Repository;

/**
 * Sets and retrieves the status for an issue.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface StatusApi
{
    public function getIssueStatus($issueNumber, Repository $repository);

    public function setIssueStatus($issueNumber, $newStatus, Repository $repository);
}
