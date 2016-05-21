<?php

namespace AppBundle\Subscriber;

use AppBundle\Event\GitHubEvent;
use AppBundle\GitHubEvents;
use AppBundle\Issues\CommentsApiInterface;
use AppBundle\Issues\GitHub\GitHubStatusApi;
use AppBundle\Issues\Status;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NewPRInstructionCommentSubscriber implements EventSubscriberInterface
{
    private $commentsApi;

    public function __construct(CommentsApiInterface $commentsApi)
    {
        $this->commentsApi = $commentsApi;
    }

    /**
     * Adds a "Needs Review" label to new PRs.
     *
     * @param GitHubEvent $event
     */
    public function onPullRequest(GitHubEvent $event)
    {
        $data = $event->getData();
        $repository = $event->getRepository();
        if ('opened' !== $action = $data['action']) {
            $event->setResponseData(array('unsupported_action' => $action));

            return;
        }

        $needsReviewLabel = GitHubStatusApi::getNeedsReviewLabel();

        $commentTexts = StatusChangeByCommentSubscriber::getValidCommentsForDisplay();
        $commentBody = sprintf(<<<EOF
Hi there!

Thank you for your contribution! I've auto-labeled
this pull request with **%s** so that everyone knows
that this is ready to be reviewed.

*Anyone* is welcome to change the status of this pull request
by commenting with one of the following things (on its own line):

%s

Cheers!
EOF
        , $needsReviewLabel, implode("\n", $commentTexts));

        $pullRequestNumber = $data['pull_request']['number'];
        $this->commentsApi->commentOnIssue(
            $pullRequestNumber,
            $commentBody,
            $repository
        );

        $event->setResponseData(array(
            'instructions_added' => true,
        ));
    }

    public static function getSubscribedEvents()
    {
        return array(
            GitHubEvents::PULL_REQUEST => 'onPullRequest',
        );
    }
}
