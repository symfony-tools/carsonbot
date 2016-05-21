<?php

namespace AppBundle\Subscriber;

use AppBundle\Event\GitHubEvent;
use AppBundle\GitHubEvents;
use AppBundle\Issues\Status;
use AppBundle\Issues\StatusApi;
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
     *
     * @param GitHubEvent $event
     */
    public function onIssues(GitHubEvent $event)
    {
        $data = $event->getData();
        $repository = $event->getRepository();
        if ('labeled' !== $action = $data['action']) {
            $event->setResponseData(array('unsupported_action' => $action));

            return;
        }

        $responseData = array('issue' => $issueNumber = $data['issue']['number']);
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
        return array(
            GitHubEvents::ISSUES => 'onIssues',
        );
    }
}
