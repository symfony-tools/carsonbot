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
    public function __construct(
        private readonly MilestoneApi $milestonesApi,
    ) {
    }

    /**
     * Sets milestone on merged PRs.
     */
    public function onPullRequest(GitHubEvent $event): void
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
