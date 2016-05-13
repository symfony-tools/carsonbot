<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Listener;

use AppBundle\Event\GitHubEvent;
use AppBundle\GitHubEvents;
use AppBundle\Issues\IssueListener;
use AppBundle\Issues\Status;

/**
 * SymfonyIssueListener.
 *
 * @author Jules Pietri <jules@heahprod.com>
 */
class SymfonyIssueListener extends IssueListener
{
    protected static $triggerWordToStatus = [
        'needs review' => Status::NEEDS_REVIEW,
        'needs work' => Status::NEEDS_WORK,
        'works for me' => Status::WORKS_FOR_ME,
        'reviewed' => Status::REVIEWED,
        'needs comments' => Status::NEEDS_COMMENTS,
    ];

    protected static $privateTriggerWordToStatus = [
        'ready' => Status::READY,
    ];

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
        if ('bug' !== strtolower($data['label']['name']) || null !== $currentStatus = $this->getIssueStatus($issueNumber)) {
            $responseData['status_change'] = null;
            $event->setResponseData($responseData);

            return;
        }

        $newStatus = Status::NEEDS_REVIEW;

        $this->setIssueStatus($issueNumber, $newStatus);
        $responseData['status_change'] = $newStatus;

        $event->setResponseData($responseData);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            GitHubEvents::ISSUE_COMMENT => 'onIssueComment',
            GitHubEvents::PULL_REQUEST => 'onPullRequest',
            GitHubEvents::ISSUES => 'onIssues',
        );
    }
}
