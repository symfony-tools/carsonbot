<?php

namespace App\Subscriber;

use App\Api\Status\Status;
use App\Api\Status\StatusApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use Psr\Log\LoggerInterface;

class StatusChangeByCommentSubscriber extends AbstractStatusChangeSubscriber
{
    public function __construct(
        StatusApi $statusApi,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($statusApi);
    }

    /**
     * Parses the text of the comment and looks for keywords to see
     * if this should cause any status change.
     */
    public function onIssueComment(GitHubEvent $event): void
    {
        $data = $event->getData();
        $repository = $event->getRepository();
        $issueNumber = $data['issue']['number'];
        $newStatus = $this->parseStatusFromText($data['comment']['body']);

        $isUserAllowedToReview = ($data['issue']['user']['login'] !== $data['comment']['user']['login']);

        if (Status::REVIEWED === $newStatus && !$isUserAllowedToReview) {
            $newStatus = null;
        }

        $event->setResponseData([
            'issue' => $issueNumber,
            'status_change' => $newStatus,
        ]);

        if (null === $newStatus) {
            return;
        }

        $this->logger->debug(sprintf('Setting issue number %s to status %s', $issueNumber, $newStatus));
        $this->statusApi->setIssueStatus($issueNumber, $newStatus, $repository);
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            GitHubEvents::ISSUE_COMMENT => 'onIssueComment',
        ];
    }
}
