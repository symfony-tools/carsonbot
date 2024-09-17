<?php

namespace App\Subscriber;

use App\Api\Status\Status;
use App\Api\Status\StatusApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Service\WipParser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NeedsReviewNewPRSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly StatusApi $statusApi,
    ) {
    }

    /**
     * Adds a "Needs Review" label to new PRs.
     */
    public function onPullRequest(GitHubEvent $event): void
    {
        $data = $event->getData();
        $repository = $event->getRepository();
        if (!in_array($data['action'], ['opened', 'ready_for_review']) || ($data['pull_request']['draft'] ?? false)) {
            return;
        }

        $newStatus = Status::NEEDS_REVIEW;
        if (WipParser::matchTitle($data['pull_request']['title'])) {
            $newStatus = Status::NEEDS_WORK;
        }

        $pullRequestNumber = $data['pull_request']['number'];
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
            GitHubEvents::PULL_REQUEST => 'onPullRequest',
        ];
    }
}
