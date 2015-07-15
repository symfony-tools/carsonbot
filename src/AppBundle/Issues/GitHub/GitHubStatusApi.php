<?php

namespace AppBundle\Issues\GitHub;

use AppBundle\Issues\Status;
use AppBundle\Issues\StatusApi;

class GitHubStatusApi implements StatusApi
{
    private $statusToLabel = [
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

    /**
     * @var string
     */
    private $repositoryUsername;

    /**
     * @var string
     */
    private $repositoryName;

    public function __construct(CachedLabelsApi $labelsApi, $repositoryUsername, $repositoryName)
    {
        $this->labelsApi = $labelsApi;
        $this->labelToStatus = array_flip($this->statusToLabel);
        $this->repositoryUsername = $repositoryUsername;
        $this->repositoryName = $repositoryName;
    }

    /**
     * @param integer $issueNumber The GitHub issue number
     * @param string  $newStatus A Status::* constant
     */
    public function setIssueStatus($issueNumber, $newStatus)
    {
        if (!isset($this->statusToLabel[$newStatus])) {
            throw new \InvalidArgumentException(sprintf('Invalid status "%s"', $newStatus));
        }

        $newLabel = $this->statusToLabel[$newStatus];
        $currentLabels = $this->labelsApi->getIssueLabels($issueNumber);
        $addLabel = true;

        foreach ($currentLabels as $label) {
            // Ignore non-status
            if (!isset($this->labelToStatus[$label])) {
                continue;
            }

            if ($newLabel === $label) {
                $addLabel = false;
                continue;
            }

            // Remove other statuses
            $this->labelsApi->removeIssueLabel($issueNumber, $label);
        }

        // Ignored if the label is already set
        if ($addLabel) {
            $this->labelsApi->addIssueLabel($issueNumber, $newLabel);
        }
    }

    public function getIssueStatus($issueNumber)
    {
        $currentLabels = $this->labelsApi->getIssueLabels($issueNumber);

        foreach ($currentLabels as $label) {
            if (isset($this->labelToStatus[$label])) {
                return $this->labelToStatus[$label];
            }
        }

        // No status set
        return null;
    }

    public function getNeedsReviewUrl()
    {
        return sprintf(
            'https://github.com/%s/%s/labels/%s',
            $this->repositoryUsername,
            $this->repositoryName,
            rawurlencode($this->statusToLabel[Status::NEEDS_REVIEW])
        );
    }
}
