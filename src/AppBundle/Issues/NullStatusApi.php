<?php

namespace AppBundle\Issues;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class NullStatusApi implements StatusApi
{
    public function getIssueStatus($issueNumber)
    {
    }

    public function setIssueStatus($issueNumber, $newStatus)
    {
    }

    public function getNeedsReviewUrl()
    {
    }
}
