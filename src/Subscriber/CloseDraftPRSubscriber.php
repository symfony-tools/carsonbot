<?php

namespace App\Subscriber;

use App\Api\Issue\IssueApi;
use App\Entity\Task;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Service\ComplementGenerator;
use App\Service\TaskScheduler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class CloseDraftPRSubscriber implements EventSubscriberInterface
{
    private $issueApi;
    private $complementGenerator;
    private $scheduler;

    public function __construct(IssueApi $issueApi, ComplementGenerator $complementGenerator, TaskScheduler $scheduler)
    {
        $this->issueApi = $issueApi;
        $this->complementGenerator = $complementGenerator;
        $this->scheduler = $scheduler;
    }

    public function onPullRequest(GitHubEvent $event)
    {
        $data = $event->getData();
        $repository = $event->getRepository();
        if ('opened' !== $data['action'] || !($data['pull_request']['draft'] ?? false)) {
            return;
        }

        $number = $data['pull_request']['number'];
        $complement = $this->complementGenerator->getPullRequestComplement();
        $this->issueApi->commentOnIssue($repository, $number, <<<TXT
Hey!

$complement

Could you please click the "ready for review" button? Or maybe close this PR and open a new one when you are done.
Note that a pull request does not have to be "perfect" or "ready for merge" when you first open it, but it should be ready for a first review.

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

    public static function getSubscribedEvents()
    {
        return [
            GitHubEvents::PULL_REQUEST => 'onPullRequest',
        ];
    }
}
