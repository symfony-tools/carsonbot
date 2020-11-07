<?php

namespace App\Subscriber;

use App\Api\Status\Status;
use App\Api\Status\StatusApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NeedsReviewNewPRSubscriber implements EventSubscriberInterface
{
    private $statusApi;

    public function __construct(StatusApi $statusApi)
    {
        $this->statusApi = $statusApi;
    }

    /**
     * Adds a "Needs Review" label to new PRs.
     */
    public function onPullRequest(GitHubEvent $event)
    {
        $data = $event->getData();
        $repository = $event->getRepository();
        if (!in_array($data['action'], ['opened', 'ready_for_review']) || ($data['pull_request']['draft'] ?? false)) {
            return;
        }

        $pullRequestNumber = $data['pull_request']['number'];
        $newStatus = Status::NEEDS_REVIEW;

        $this->statusApi->setIssueStatus($pullRequestNumber, $newStatus, $repository);

        $event->setResponseData([
            'pull_request' => $pullRequestNumber,
            'status_change' => $newStatus,
        ]);
    }

    public static function getSubscribedEvents()
    {
        return [
            GitHubEvents::PULL_REQUEST => 'onPullRequest',
        ];
    }
}
