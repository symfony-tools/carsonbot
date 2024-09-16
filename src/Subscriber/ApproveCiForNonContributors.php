<?php

namespace App\Subscriber;

use App\Api\Workflow\WorkflowApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Make sure CI is running for all PRs.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class ApproveCiForNonContributors implements EventSubscriberInterface
{
    public function __construct(
        private readonly WorkflowApi $workflowApi,
    ) {
    }

    public function onPullRequest(GitHubEvent $event): void
    {
        $data = $event->getData();
        if (!in_array($data['action'], ['opened', 'reopened', 'synchronize'])) {
            return;
        }

        $repository = $event->getRepository();
        $headRepository = $data['pull_request']['head']['repo']['full_name'];
        $headBranch = $data['pull_request']['head']['ref'];
        $this->workflowApi->approveWorkflowsForPullRequest($repository, $headRepository, $headBranch);

        $event->setResponseData(['approved_run' => true]);
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
