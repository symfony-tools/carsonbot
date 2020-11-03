<?php

namespace App\Subscriber;

use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Issues\GitHub\MilestonesApi;
use App\Sercice\SymfonyVersionProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class MilestoneNewPRSubscriber implements EventSubscriberInterface
{
    private $milestonesApi;
    private $symfonyVersionProvider;
    private $ignoreCurrentVersion;

    public function __construct(MilestonesApi $milestonesApi, SymfonyVersionProvider $symfonyVersionProvider, $ignoreCurrentVersion = false)
    {
        $this->milestonesApi = $milestonesApi;
        $this->symfonyVersionProvider = $symfonyVersionProvider;
        $this->ignoreCurrentVersion = $ignoreCurrentVersion;
    }

    /**
     * Sets milestone on PRs that target non-default branch.
     */
    public function onPullRequest(GitHubEvent $event)
    {
        $data = $event->getData();
        $repository = $event->getRepository();
        if ('opened' !== $data['action']) {
            return;
        }

        $targetBranch = $data['pull_request']['base']['ref'];
        if ($targetBranch === $data['repository']['default_branch']) {
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
