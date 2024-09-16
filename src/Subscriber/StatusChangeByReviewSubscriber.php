<?php

namespace App\Subscriber;

use App\Api\Status\Status;
use App\Api\Status\StatusApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use Psr\Log\LoggerInterface;

/**
 * Changes the status when a new review is submitted.
 *
 * @author Wouter de Jong <wouter@wouterj.nl>
 */
class StatusChangeByReviewSubscriber extends AbstractStatusChangeSubscriber
{
    public function __construct(
        StatusApi $statusApi,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($statusApi);
    }

    /**
     * Sets the status based on the review state (approved/changes requested)
     * or the review body (using the Status: keyword).
     */
    public function onReview(GitHubEvent $event): void
    {
        $data = $event->getData();
        if ('submitted' !== $data['action']) {
            return;
        }

        $repository = $event->getRepository();
        $pullRequestNumber = $data['pull_request']['number'];
        $newStatus = null;

        // Set status based on review state
        $newStatus = match (strtolower($data['review']['state'])) {
            'approved' => Status::REVIEWED,
            'changes_requested' => Status::NEEDS_WORK,
            default => $this->parseStatusFromText($data['review']['body']),
        };

        if (Status::REVIEWED === $newStatus && false === $this->isUserAllowedToReview($data)) {
            $newStatus = null;
        }

        $event->setResponseData([
            'pull_request' => $pullRequestNumber,
            'status_change' => $newStatus,
        ]);

        if (null === $newStatus) {
            return;
        }

        $this->logger->debug(sprintf('Setting issue number %s to status %s', $pullRequestNumber, $newStatus));
        $this->statusApi->setIssueStatus($pullRequestNumber, $newStatus, $repository);
    }

    /**
     * Sets the status to needs review when a review is requested.
     */
    public function onReviewRequested(GitHubEvent $event): void
    {
        $data = $event->getData();
        if ('review_requested' !== $data['action']) {
            return;
        }

        $repository = $event->getRepository();
        $pullRequestNumber = $data['pull_request']['number'];
        $newStatus = Status::NEEDS_REVIEW;

        $this->logger->debug(sprintf('Setting issue number %s to status %s', $pullRequestNumber, $newStatus));
        $this->statusApi->setIssueStatus($pullRequestNumber, $newStatus, $repository);

        $event->setResponseData([
            'pull_request' => $pullRequestNumber,
            'status_change' => $newStatus,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            GitHubEvents::PULL_REQUEST_REVIEW => 'onReview',
            GitHubEvents::PULL_REQUEST => 'onReviewRequested',
        ];
    }

    private function isUserAllowedToReview(array $data): bool
    {
        return $data['pull_request']['user']['login'] !== $data['review']['user']['login'];
    }
}
