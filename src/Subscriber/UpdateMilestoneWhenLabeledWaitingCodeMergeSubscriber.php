<?php

namespace App\Subscriber;

use App\Api\Milestone\MilestoneApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * When label "Waiting Code Merge" is added, then set milestone to "next".
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class UpdateMilestoneWhenLabeledWaitingCodeMergeSubscriber implements EventSubscriberInterface
{
    private MilestoneApi $milestoneApi;

    public function __construct(MilestoneApi $milestoneApi)
    {
        $this->milestoneApi = $milestoneApi;
    }

    public function onLabel(GitHubEvent $event)
    {
        $data = $event->getData();
        $action = $data['action'];
        if ('labeled' !== $action) {
            return;
        }

        foreach ($data['pull_request']['labels'] ?? $data['issue']['labels'] ?? [] as $label) {
            if ('Waiting Code Merge' === $label['name']) {
                $repository = $event->getRepository();
                $number = $data['number'] ?? $data['issue']['number'];
                $this->milestoneApi->updateMilestone($repository, $number, 'next');

                $event->setResponseData([
                    'pull_request' => $number,
                    'milestone' => 'next',
                ]);

                return;
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            GitHubEvents::PULL_REQUEST => 'onLabel',
            GitHubEvents::ISSUES => 'onLabel',
        ];
    }
}
