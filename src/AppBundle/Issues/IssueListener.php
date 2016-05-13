<?php

namespace AppBundle\Issues;

use AppBundle\Event\GitHubEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class IssueListener implements EventSubscriberInterface
{
    protected static $triggerWordToStatus;
    protected static $privateTriggerWordToStatus;

    /**
     * @var StatusApi
     */
    private $statusApi;

    final public function __construct(StatusApi $statusApi)
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
        $user = $data['comment']['user']['login'];
        $statuses = static::$triggerWordToStatus;

        if (in_array($user, $event->getMaintainers(), true)) {
            $statuses += static::$privateTriggerWordToStatus;
        }

        $triggerWord = implode('|', array_keys($statuses));
        $formatting = '[\\s\\*]*';
        // Match first character after "status:"
        // Case insensitive ("i"), ignores formatting with "*" before or after the ":"
        $pattern = "~(?=\n|^)${formatting}status${formatting}:${formatting}[\"']?($triggerWord)[\"']?${formatting}[.!]?${formatting}(?<=\r\n|\n|$)~i";

        if (preg_match_all($pattern, $data['comment']['body'], $matches)) {
            // Second subpattern = first status character
            $newStatus = $statuses[strtolower(end($matches[1]))];

            $this->setIssueStatus($issueNumber, $newStatus);
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

            $this->setIssueStatus($pullRequestNumber, $newStatus);

            $responseData = array(
                'pull_request' => $pullRequestNumber,
                'status_change' => $newStatus,
            );
        }

        $event->setResponseData($responseData);
    }

    final protected function getIssueStatus($issueNumber)
    {
        return $this->statusApi->getIssueStatus($issueNumber);
    }

    final protected function setIssueStatus($issueNumber, $status)
    {
        return $this->statusApi->setIssueStatus($issueNumber, $status);
    }
}
