<?php

namespace App\Subscriber;

use App\Api\PullRequest\PullRequestApi;
use App\Command\SuggestReviewerCommand;
use App\Entity\Task;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Service\TaskScheduler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class FindReviewerSubscriber implements EventSubscriberInterface
{
    private $pullRequestApi;
    private $botUsername;
    private $scheduler;

    public function __construct(PullRequestApi $pullRequestApi, TaskScheduler $scheduler, string $botUsername)
    {
        $this->pullRequestApi = $pullRequestApi;
        $this->botUsername = $botUsername;
        $this->scheduler = $scheduler;
    }

    public function onPullRequest(GitHubEvent $event)
    {
        $data = $event->getData();
        if (!in_array($data['action'], ['opened', 'ready_for_review']) || ($data['pull_request']['draft'] ?? false)) {
            return;
        }

        $repository = $event->getRepository();

        // set scheduled task to run in 20 hours
        $this->scheduler->runLater($repository, (int) $data['number'], Task::ACTION_SUGGEST_REVIEWER, new \DateTimeImmutable('+20hours'));
    }

    /**
     * When somebody makes a comment on an issue or pull request.
     * If it includes the bot name and "review" on the same line, then try to find reviewer.
     */
    public function onComment(GitHubEvent $event)
    {
        $data = $event->getData();
        if ('created' !== $data['action']) {
            return;
        }

        if (false === strpos($data['comment']['body'], $this->botUsername)) {
            return;
        }

        // Search for "review"
        if (preg_match('~\@'.$this->botUsername.'(\W[^(status)].*|\W)(\breviewer\b|\breview\b)~i', $data['comment']['body'] ?? '')) {
            $number = $data['issue']['number'];
            $this->pullRequestApi->findReviewer($event->getRepository(), $number, SuggestReviewerCommand::TYPE_DEMAND);
            $event->setResponseData([
                'issue' => $number,
                'suggest-review' => true,
            ]);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            GitHubEvents::PULL_REQUEST => 'onPullRequest',
            GitHubEvents::ISSUE_COMMENT => 'onComment',
        ];
    }
}
