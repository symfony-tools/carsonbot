<?php

namespace AppBundle\Issues;

/**
 * Sets and retrieves the status for an issue.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface StatusApi
{
    public function getIssueStatus($issueNumber);

    public function setIssueStatus($issueNumber, $newStatus);

    public function getNeedsReviewUrl();
}
