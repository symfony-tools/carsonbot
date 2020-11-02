<?php

namespace App\Subscriber;

use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Issues\Status;
use App\Issues\StatusApi;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BugLabelNewIssueSubscriber implements EventSubscriberInterface
{
    /**
     * @var StatusApi
     */
    private $statusApi;

    public function __construct(StatusApi $statusApi)
    {
        $this->statusApi = $statusApi;
    }

    /**
     * Changes "Bug" issues to "Needs Review".
     */
    public function onIssues(GitHubEvent $event)
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

    public static function getSubscribedEvents()
    {
        return [
            GitHubEvents::ISSUES => 'onIssues',
        ];
    }
}
