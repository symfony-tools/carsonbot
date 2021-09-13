<?php

namespace App\Subscriber;

use App\Api\Milestone\MilestoneApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Service\SymfonyVersionProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class MilestoneNewPRSubscriber implements EventSubscriberInterface
{
    private $milestonesApi;
    private $symfonyVersionProvider;
    private $ignoreCurrentVersion;
    private $ignoreDefaultBranch;

    public function __construct(MilestoneApi $milestonesApi, SymfonyVersionProvider $symfonyVersionProvider, $ignoreCurrentVersion = false, $ignoreDefaultBranch = false)
    {
        $this->milestonesApi = $milestonesApi;
        $this->symfonyVersionProvider = $symfonyVersionProvider;
        $this->ignoreCurrentVersion = $ignoreCurrentVersion;
        $this->ignoreDefaultBranch = $ignoreDefaultBranch;
    }

    /**
     * Sets milestone on PRs.
     */
    public function onPullRequest(GitHubEvent $event)
    {
        $data = $event->getData();
        $repository = $event->getRepository();
        if (!in_array($data['action'], ['opened', 'ready_for_review']) || ($data['pull_request']['draft'] ?? false)) {
            return;
        }

        $targetBranch = $data['pull_request']['base']['ref'];
        if ($this->ignoreDefaultBranch && $targetBranch === $data['repository']['default_branch']) {
            return;
        }

        if ($this->ignoreCurrentVersion && $targetBranch === $this->symfonyVersionProvider->getCurrentVersion()) {
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
