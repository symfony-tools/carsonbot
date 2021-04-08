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
Please note that you need squash your commits before this PR can be merged. The maintainer can also squash the commits for you, but then you need to “Allow edits from maintainer” (there is a checkbox in the sidebar of the PR).

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
