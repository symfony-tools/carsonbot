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
class SymfonyDocsIssueListener extends IssueListener
{
    protected static $triggerWordToStatus = [
        'needs review' => Status::NEEDS_REVIEW,
        'needs comments' => Status::NEEDS_COMMENTS,
    ];
    protected static $privateTriggerWordToStatus = [
        'in progress' => Status::IN_PROGRESS,
        'finished' => Status::FINISHED,
        'on hold' => Status::ON_HOLD,
    ];

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            GitHubEvents::ISSUE_COMMENT => 'onIssueComment',
            GitHubEvents::PULL_REQUEST => 'onPullRequest',
        );
    }
}
