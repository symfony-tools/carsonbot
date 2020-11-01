<?php

namespace App\Subscriber;

use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Issues\Status;
use App\Issues\StatusApi;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Changes the status from "Needs Work" to "Needs Review" when pushing new
 * commits.
 *
 * @author Wouter de Jong <wouter@wouterj.nl>
 */
class StatusChangeOnPushSubscriber implements EventSubscriberInterface
{
    private $statusApi;

    public function __construct(StatusApi $statusApi)
    {
        $this->statusApi = $statusApi;
    }

    public function onPullRequest(GitHubEvent $event)
    {
        $data = $event->getData();
        if ('synchronize' !== $data['action']) {
            return;
        }

        $repository = $event->getRepository();
        $pullRequestNumber = $data['pull_request']['number'];
        $responseData = ['pull_request' => $pullRequestNumber];
        $currentStatus = $this->statusApi->getIssueStatus($pullRequestNumber, $repository);
        $pullRequestTitle = $data['pull_request']['title'];

        if (Status::NEEDS_WORK !== $currentStatus || false !== stripos($pullRequestTitle, '[wip]')) {
            $responseData['status_change'] = null;
            $event->setResponseData($responseData);

            return;
        }

        $newStatus = Status::NEEDS_REVIEW;

        $this->statusApi->setIssueStatus($pullRequestNumber, $newStatus, $repository);
        $responseData['status_change'] = $newStatus;

        $event->setResponseData($responseData);
    }

    public static function getSubscribedEvents()
    {
        return [
            GitHubEvents::PULL_REQUEST => 'onPullRequest',
        ];
    }
}
