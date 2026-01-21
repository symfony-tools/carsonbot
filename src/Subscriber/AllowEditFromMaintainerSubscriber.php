<?php

namespace App\Subscriber;

use App\Api\Issue\IssueApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class AllowEditFromMaintainerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly IssueApi $commentsApi,
    ) {
    }

    public function onPullRequest(GitHubEvent $event): void
    {
        $data = $event->getData();
        if (!in_array($data['action'], ['opened', 'ready_for_review', 'edited']) || ($data['pull_request']['draft'] ?? false)) {
            return;
        }

        if ($data['repository']['full_name'] === $data['pull_request']['head']['repo']['full_name']) {
            return;
        }

        $repository = $event->getRepository();
        $pullRequestNumber = $data['pull_request']['number'];
        $commentId = $this->commentsApi->findBotComment($repository, $pullRequestNumber, 'Allow edits from maintainer');

        if ($data['pull_request']['maintainer_can_modify'] ?? true) {
            if ($commentId) {
                $this->commentsApi->removeComment($event->getRepository(), $commentId);
            }

            return;
        }

        // Avoid duplicate comments
        if ($commentId) {
            return;
        }

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
