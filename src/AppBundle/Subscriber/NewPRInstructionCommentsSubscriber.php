<?php

namespace AppBundle\Subscriber;

use AppBundle\Event\GitHubEvent;
use AppBundle\GitHubEvents;
use AppBundle\Issues\Status;
use AppBundle\Issues\StatusApi;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NewPRInstructionCommentsSubscriber implements EventSubscriberInterface
{
    private $statusApi;

    public function __construct(StatusApi $statusApi)
    {
        $this->statusApi = $statusApi;
    }

    /**
     * Adds a "Needs Review" label to new PRs.
     *
     * @param GitHubEvent $event
     */
    public function onPullRequest(GitHubEvent $event)
    {
        $data = $event->getData();
        $repository = $event->getRepository();
        if ('opened' !== $action = $data['action']) {
            $event->setResponseData(array('unsupported_action' => $action));

            return;
        }

        $pullRequestNumber = $data['pull_request']['number'];

        // todo - comment! and register this as a service
        // and add a simple test!

        $event->setResponseData(array(
            'instructions_added' => true,
        ));
    }

    public static function getSubscribedEvents()
    {
        return array(
            GitHubEvents::PULL_REQUEST => 'onPullRequest',
        );
    }
}
