<?php

namespace App\Subscriber;

use App\Api\Issue\IssueApi;
use App\Api\PullRequest\PullRequestApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class AllowEditFromMaintainerSubscriber implements EventSubscriberInterface
{
    private $commentsApi;
    private $pullRequestApi;

    public function __construct(IssueApi $commentsApi, PullRequestApi $pullRequestApi)
    {
        $this->commentsApi = $commentsApi;
        $this->pullRequestApi = $pullRequestApi;
    }

    public function onPullRequest(GitHubEvent $event)
    {
        $data = $event->getData();
        if (!in_array($data['action'], ['opened', 'ready_for_review']) || ($data['pull_request']['draft'] ?? false)) {
            return;
        }

        if ($data['pull_request']['maintainer_can_modify'] ?? true) {
            return;
        }

        $repository = $event->getRepository();
        $pullRequestNumber = $data['pull_request']['number'];
        $this->commentsApi->commentOnIssue($repository, $pullRequestNumber, <<<TXT
It looks like you unchecked the "Allow edits from maintainer" box. That is fine, but please note that if you have multiple commits, you'll need to squash your commits into one before this can be merged. Or, you can check the "Allow edits from maintainers" box and the maintainer can squash for you.

Cheers!

Carsonbot
TXT
);

        $event->setResponseData([
            'pull_request' => $pullRequestNumber,
            'squash_comment' => true,
        ]);
    }

    public static function getSubscribedEvents()
    {
        return [
            GitHubEvents::PULL_REQUEST => 'onPullRequest',
        ];
    }
}
