<?php

namespace AppBundle\Issues;

use AppBundle\Event\GitHubEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class IssueListener implements EventSubscriberInterface
{
    protected static $triggerWordToStatus = [
        'needs review' => Status::NEEDS_REVIEW,
        'needs work' => Status::NEEDS_WORK,
        'works for me' => Status::WORKS_FOR_ME,
        'reviewed' => Status::REVIEWED,
    ];

    /**
     * @var StatusApi
     */
    protected $statusApi;

    public function __construct(StatusApi $statusApi)
    {
        $this->statusApi = $statusApi;
    }

    /**
     * Parses the text of the comment and looks for keywords to see
     * if this should cause any status change.
     *
     * @param GitHubEvent $event
     */
    public function onIssueComment(GitHubEvent $event)
    {
        $data = $event->getData();
        $newStatus = null;
        $issueNumber = $data['issue']['number'];
        $triggerWord = implode('|', array_keys(self::$triggerWordToStatus));
        $formatting = '[\\s\\*]*';

        // Match first character after "status:"
        // Case insensitive ("i"), ignores formatting with "*" before or after the ":"
        $pattern = "~(?=\n|^)${formatting}status${formatting}:${formatting}[\"']?($triggerWord)[\"']?${formatting}[.!]?${formatting}(?<=\r\n|\n|$)~i";

        if (preg_match_all($pattern, $data['comment']['body'], $matches)) {
            // Second subpattern = first status character
            $newStatus = self::$triggerWordToStatus[strtolower(end($matches[1]))];

            $this->statusApi->setIssueStatus($issueNumber, $newStatus);
        }

        $event->setResponseData(array(
            'issue' => $issueNumber,
            'status_change' => $newStatus,
        ));
    }

    /**
     * Adds a "Needs Review" label to new PRs.
     *
     * @param GitHubEvent $event
     */
    public function onPullRequest(GitHubEvent $event)
    {
        $data = $event->getData();
        if ('opened' !== $action = $data['action']) {
            $responseData = array('unsupported_action' => $action);
        } else {
            $pullRequestNumber = $data['pull_request']['number'];
            $newStatus = Status::NEEDS_REVIEW;

            $this->statusApi->setIssueStatus($pullRequestNumber, $newStatus);

            $responseData = array(
                'pull_request' => $pullRequestNumber,
                'status_change' => $newStatus,
            );
        }

        $event->setResponseData($responseData);
    }

    /**
     * Changes "Bug" issues to "Needs Review".
     *
     * @param GitHubEvent $event
     */
    public function onIssues(GitHubEvent $event)
    {
        $data = $event->getData();
        if ('labeled' !== $action = $data['action']) {
            $event->setResponseData(array('unsupported_action' => $action));

            return;
        }

        $responseData = array('issue' => $issueNumber = $data['issue']['number']);
        // Ignore non-bugs or issue which already has a status
        if ('bug' !== strtolower($data['label']['name']) || null !== $currentStatus = $this->statusApi->getIssueStatus($issueNumber)) {
            $responseData['status_change'] = null;
            $event->setResponseData($responseData);

            return;
        }

        $newStatus = Status::NEEDS_REVIEW;

        $this->statusApi->setIssueStatus($issueNumber, $newStatus);
        $responseData['status_change'] = $newStatus;

        $event->setResponseData($responseData);
    }
}
