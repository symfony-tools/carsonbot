<?php

namespace App\Subscriber;

use App\Api\Status\Status;
use App\Api\Status\StatusApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BugLabelNewIssueSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly StatusApi $statusApi,
    ) {
    }

    /**
     * Changes "Bug" issues to "Needs Review".
     */
    public function onIssues(GitHubEvent $event): void
    {
        $data = $event->getData();
        $repository = $event->getRepository();
        if ('labeled' !== $action = $data['action']) {
            return;
        }

        $responseData = ['issue' => $issueNumber = $data['issue']['number']];
        // Ignore non-bugs or issue which already has a status
        if ('bug' !== strtolower($data['label']['name']) || null !== $this->statusApi->getIssueStatus($issueNumber, $repository)) {
            $responseData['status_change'] = null;
            $event->setResponseData($responseData);

            return;
        }

        $newStatus = Status::NEEDS_REVIEW;

        $this->statusApi->setIssueStatus($issueNumber, $newStatus, $repository);
        $responseData['status_change'] = $newStatus;

        $event->setResponseData($responseData);
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            GitHubEvents::ISSUES => 'onIssues',
        ];
    }
}
