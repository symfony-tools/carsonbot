<?php

namespace App\Api\Status;

use App\Api\Label\LabelApi;
use App\Model\Repository;
use Psr\Log\LoggerInterface;

class GitHubStatusApi implements StatusApi
{
    /**
     * @var array<string, string>
     */
    private const array STATUS_TO_LABEL = [
        Status::NEEDS_REVIEW => 'Status: Needs Review',
        Status::NEEDS_WORK => 'Status: Needs Work',
        Status::WORKS_FOR_ME => 'Status: Works for me',
        Status::REVIEWED => 'Status: Reviewed',
        Status::WAITING_FEEDBACK => 'Status: Waiting feedback',
    ];

    /**
     * @var array<string, string>
     */
    private array $labelToStatus;

    public function __construct(
        private readonly LabelApi $labelsApi,
        private readonly LoggerInterface $logger,
    ) {
        $this->labelToStatus = array_flip(self::STATUS_TO_LABEL);
    }

    /**
     * @param int         $issueNumber The GitHub issue number
     * @param string|null $newStatus   A Status::* constant
     */
    public function setIssueStatus(int $issueNumber, ?string $newStatus, Repository $repository): void
    {
        if (null !== $newStatus && !isset(self::STATUS_TO_LABEL[$newStatus])) {
            throw new \InvalidArgumentException(sprintf('Invalid status "%s"', $newStatus));
        }

        $newLabel = null === $newStatus ? null : self::STATUS_TO_LABEL[$newStatus];
        $this->logger->info(sprintf('Fetching issue labels for issue %s, repository %s', $issueNumber, $repository->getFullName()));
        $currentLabels = $this->labelsApi->getIssueLabels($issueNumber, $repository);

        $this->logger->info(sprintf('Fetched the following labels: %s', implode(', ', $currentLabels)));

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
            $this->logger->debug(sprintf('Removing label %s from issue %s on repository %s', $label, $issueNumber, $repository->getFullName()));
            $this->labelsApi->removeIssueLabel($issueNumber, $label, $repository);
        }

        // Ignored if the label is already set
        if ($addLabel && $newLabel) {
            $this->logger->debug(sprintf('Adding label "%s" to issue %s on repository %s', $newLabel, $issueNumber, $repository->getFullName()));
            $this->labelsApi->addIssueLabel($issueNumber, $newLabel, $repository);
            $this->logger->debug('Label added!');
        }
    }

    public function getIssueStatus(int $issueNumber, Repository $repository): ?string
    {
        $currentLabels = $this->labelsApi->getIssueLabels($issueNumber, $repository);

        foreach ($currentLabels as $label) {
            if (isset($this->labelToStatus[$label])) {
                return $this->labelToStatus[$label];
            }
        }

        // No status set
        return null;
    }

    public static function getNeedsReviewLabel(): string
    {
        return self::STATUS_TO_LABEL[Status::NEEDS_REVIEW];
    }
}
