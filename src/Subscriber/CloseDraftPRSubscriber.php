<?php

namespace App\Subscriber;

use App\Api\Issue\IssueApi;
use App\Entity\Task;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Service\TaskScheduler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class CloseDraftPRSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly IssueApi $issueApi,
        private readonly TaskScheduler $scheduler,
    ) {
    }

    public function onPullRequest(GitHubEvent $event): void
    {
        $data = $event->getData();
        $repository = $event->getRepository();
        if ('opened' !== $data['action'] || !($data['pull_request']['draft'] ?? false)) {
            return;
        }

        $number = (int) $data['pull_request']['number'];
        $this->issueApi->commentOnIssue($repository, $number, <<<TXT
Hey!

To help keep things organized, we don't allow "Draft" pull requests. Could you please click the "ready for review" button or close this PR and open a new one when you are done?

Note that a pull request does not have to be "perfect" or "ready for merge" when you first open it. We just want it to be ready for a first review.

Cheers!

Carsonbot
TXT
        );

        // Add a scheduled task to close the PR within 1 hour.
        $this->scheduler->runLater($repository, $number, Task::ACTION_CLOSE_DRAFT, new \DateTimeImmutable('+1hour'));

        $event->setResponseData([
            'pull_request' => $number,
            'draft_comment' => true,
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
