<?php

namespace AppBundle\Issues\GitHub;

use AppBundle\Issues\Status;
use AppBundle\Issues\StatusApi;
use AppBundle\Repository\Repository;
use Psr\Log\LoggerInterface;

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
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(CachedLabelsApi $labelsApi, LoggerInterface $logger)
    {
        $this->labelsApi = $labelsApi;
        $this->labelToStatus = array_flip(self::$statusToLabel);
        $this->logger = $logger;
    }

    /**
     * @param int        $issueNumber The GitHub issue number
     * @param string     $newStatus   A Status::* constant
     * @param Repository $repository
     */
    public function setIssueStatus($issueNumber, $newStatus, Repository $repository)
    {
        if (!isset(self::$statusToLabel[$newStatus])) {
            throw new \InvalidArgumentException(sprintf('Invalid status "%s"', $newStatus));
        }

        $newLabel = self::$statusToLabel[$newStatus];
        $this->logger->info(sprintf(
            'Fetching issue labels for issue %s, repository %s',
            $issueNumber,
            $repository->getFullName()
        ));
        $currentLabels = $this->labelsApi->getIssueLabels($issueNumber, $repository);

        $this->logger->info(sprintf(
            'Fetched the following labels: %s',
            implode(', ', $currentLabels)
        ));

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
            $this->logger->debug(sprintf(
                'Removing label %s from issue %s on repository %s',
                $label,
                $issueNumber,
                $repository->getFullName()
            ));
            $this->labelsApi->removeIssueLabel($issueNumber, $label, $repository);
        }

        // Ignored if the label is already set
        if ($addLabel) {
            $this->logger->debug(sprintf(
                'Adding label "%s" to issue %s on repository %s',
                $newLabel,
                $issueNumber,
                $repository->getFullName()
            ));
            $this->labelsApi->addIssueLabel($issueNumber, $newLabel, $repository);
            $this->logger->debug('Label added!');
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
