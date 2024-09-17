<?php

namespace App\Subscriber;

use App\Api\Status\Status;
use App\Api\Status\StatusApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Service\WipParser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Changes the status from "Needs Work" to "Needs Review" when pushing new
 * commits.
 *
 * @author Wouter de Jong <wouter@wouterj.nl>
 */
class StatusChangeOnPushSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly StatusApi $statusApi,
    ) {
    }

    public function onPullRequest(GitHubEvent $event): void
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

        if (Status::NEEDS_WORK !== $currentStatus || WipParser::matchTitle($pullRequestTitle)) {
            $responseData['status_change'] = null;
            $event->setResponseData($responseData);

            return;
        }

        $newStatus = Status::NEEDS_REVIEW;

        $this->statusApi->setIssueStatus($pullRequestNumber, $newStatus, $repository);
        $responseData['status_change'] = $newStatus;

        $event->setResponseData($responseData);
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
