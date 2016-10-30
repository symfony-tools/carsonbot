<?php

namespace AppBundle\Subscriber;

use AppBundle\Event\GitHubEvent;
use AppBundle\GitHubEvents;
use AppBundle\Issues\Status;
use AppBundle\Issues\StatusApi;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StatusChangeByCommentSubscriber implements EventSubscriberInterface
{
    private static $triggerWordToStatus = [
        'needs review' => Status::NEEDS_REVIEW,
        'needs work' => Status::NEEDS_WORK,
        'works for me' => Status::WORKS_FOR_ME,
        'reviewed' => Status::REVIEWED,
    ];

    private $statusApi;
    private $logger;

    public function __construct(StatusApi $statusApi, LoggerInterface $logger)
    {
        $this->statusApi = $statusApi;
        $this->logger = $logger;
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
        $repository = $event->getRepository();
        $newStatus = null;
        $issueNumber = $data['issue']['number'];

        $triggerWord = implode('|', array_keys(static::$triggerWordToStatus));
        $formatting = '[\\s\\*]*';
        // Match first character after "status:"
        // Case insensitive ("i"), ignores formatting with "*" before or after the ":"
        $pattern = "~(?=\n|^)${formatting}status${formatting}:${formatting}[\"']?($triggerWord)[\"']?${formatting}[.!]?${formatting}(?<=\r\n|\n|$)~i";

        if (preg_match_all($pattern, $data['comment']['body'], $matches)) {
            // Second subpattern = first status character
            $newStatus = static::$triggerWordToStatus[strtolower(end($matches[1]))];

            if (Status::REVIEWED === $newStatus && false === $this->checkUserIsAllowedToReview($data)) {
                $event->setResponseData(array(
                    'issue' => $issueNumber,
                    'status_change' => null,
                ));

                return;
            }

            $this->logger->debug(sprintf('Setting issue number %s to status %s', $issueNumber, $newStatus));
            $this->statusApi->setIssueStatus($issueNumber, $newStatus, $repository);
        }

        $event->setResponseData(array(
            'issue' => $issueNumber,
            'status_change' => $newStatus,
        ));
    }

    public static function getSubscribedEvents()
    {
        return array(
            GitHubEvents::ISSUE_COMMENT => 'onIssueComment',
        );
    }

    private function checkUserIsAllowedToReview(array $data)
    {
        return $data['issue']['user']['login'] !== $data['comment']['user']['login'];
    }
}
