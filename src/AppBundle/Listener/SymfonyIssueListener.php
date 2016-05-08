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

use AppBundle\GitHubEvents;
use AppBundle\Issues\IssueListener;

/**
 * SymfonyIssueListener.
 *
 * @author Jules Pietri <jules@heahprod.com>
 */
class SymfonyIssueListener extends IssueListener
{
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