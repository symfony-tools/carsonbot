<?php

namespace App\Subscriber;

use App\Api\Milestone\MilestoneApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 */
class MilestoneMergedPRSubscriber implements EventSubscriberInterface
{
    private $milestonesApi;

    public function __construct(MilestoneApi $milestonesApi)
    {
        $this->milestonesApi = $milestonesApi;
    }

    /**
     * Sets milestone on merged PRs.
     */
    public function onPullRequest(GitHubEvent $event)
    {
        $data = $event->getData();
        $repository = $event->getRepository();

        if ('closed' !== $data['action'] || !$data['merged']) {
            return;
        }

        $targetBranch = $data['pull_request']['base']['ref'];
        if ($targetBranch === $data['milestone']) {
            return;
        }

        if (!$this->milestonesApi->exists($repository, $targetBranch)) {
            return;
        }

        $pullRequestNumber = $data['pull_request']['number'];
        $this->milestonesApi->updateMilestone($repository, $pullRequestNumber, $targetBranch);

        $event->setResponseData([
            'pull_request' => $pullRequestNumber,
            'milestone' => $targetBranch,
        ]);
    }

    public static function getSubscribedEvents()
    {
        return [
            GitHubEvents::PULL_REQUEST => 'onPullRequest',
        ];
    }
}
