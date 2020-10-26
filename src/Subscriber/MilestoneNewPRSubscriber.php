<?php

namespace App\Subscriber;

use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Issues\GitHub\CachedMilestonesApi;
use App\Issues\GitHub\MilestonesApi;
use App\Issues\Status;
use App\Issues\StatusApi;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class MilestoneNewPRSubscriber implements EventSubscriberInterface
{
    private $milestonesApi;

    public function __construct(MilestonesApi $milestonesApi)
    {
        $this->milestonesApi = $milestonesApi;
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
            return;
        }

        $targetBranch = $data['pull_request']['base']['ref'];
        if ($targetBranch === $data['repository']['default_branch']) {
            return;
        }

        if (!$this->milestonesApi->exits($repository, $targetBranch)) {
            return;
        }

        $pullRequestNumber = $data['pull_request']['number'];
        $this->milestonesApi->updateMilestone($repository, $pullRequestNumber, $targetBranch);

        $event->setResponseData(array(
            'pull_request' => $pullRequestNumber,
            'milestone' => $targetBranch,
        ));
    }

    public static function getSubscribedEvents()
    {
        return array(
            GitHubEvents::PULL_REQUEST => 'onPullRequest',
        );
    }
}
