<?php

namespace AppBundle\Issues\GitHub;

use AppBundle\Issues\Status;
use AppBundle\Issues\StatusApi;
use AppBundle\Repository\Repository;

class GitHubStatusApi implements StatusApi
{
    private static $statusToLabel = [
        Status::NEEDS_REVIEW => 'Status: Needs Review',
        Status::NEEDS_WORK => 'Status: Needs Work',
        Status::WORKS_FOR_ME => 'Status: Works for me',
        Status::REVIEWED => 'Status: Reviewed',
    ];

    private $labelToStatus = [];

    /**
     * @var CachedLabelsApi
     */
    private $labelsApi;

    public function __construct(CachedLabelsApi $labelsApi)
    {
        $this->labelsApi = $labelsApi;
        $this->labelToStatus = array_flip(self::$statusToLabel);
    }

    /**
     * @param int    $issueNumber The GitHub issue number
     * @param string $newStatus   A Status::* constant
     * @param Repository $repository
     */
    public function setIssueStatus($issueNumber, $newStatus, Repository $repository)
    {
        if (!isset(self::$statusToLabel[$newStatus])) {
            throw new \InvalidArgumentException(sprintf('Invalid status "%s"', $newStatus));
        }

        $newLabel = self::$statusToLabel[$newStatus];
        $currentLabels = $this->labelsApi->getIssueLabels($issueNumber, $repository);
        $addLabel = true;

        foreach ($currentLabels as $label) {
            // Ignore non-status, except when the bug is reviewed
            // but still marked as unconfirmed.
            if (
                !isset($this->labelToStatus[$label])
                && !(Status::REVIEWED === $newStatus && 'Unconfirmed' === $label)
            ) {
                continue;
            }

            if ($newLabel === $label) {
                $addLabel = false;
                continue;
            }

            // Remove other statuses
            $this->labelsApi->removeIssueLabel($issueNumber, $label, $repository);
        }

        // Ignored if the label is already set
        if ($addLabel) {
            $this->labelsApi->addIssueLabel($issueNumber, $newLabel, $repository);
        }
    }

    public function getIssueStatus($issueNumber, Repository $repository)
    {
        $currentLabels = $this->labelsApi->getIssueLabels($issueNumber, $repository);

        foreach ($currentLabels as $label) {
            if (isset($this->labelToStatus[$label])) {
                return $this->labelToStatus[$label];
            }
        }

        // No status set
        return;
    }

    public static function getNeedsReviewLabel()
    {
        return self::$statusToLabel[Status::NEEDS_REVIEW];
    }
}
