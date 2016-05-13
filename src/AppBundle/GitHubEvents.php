<?php

namespace AppBundle;

/**
 * GitHubEvents.
 *
 * {@link https://developer.github.com/v3/activity/events/types/}
 *
 * @author Jules Pietri <jules@heahprod.com>
 */
final class GitHubEvents
{
    /** @Event('\AppBundle\Event\GitHubEvent') */
    const COMMIT_COMMENT = 'github.commit_comment';

    /** @Event('\AppBundle\Event\GitHubEvent') */
    const CREATE = 'github.create';

    /** @Event('\AppBundle\Event\GitHubEvent') */
    const DELETE = 'github.delete';

    /** @Event('\AppBundle\Event\GitHubEvent') */
    const DEPLOYMENT = 'github.deployment';

    /** @Event('\AppBundle\Event\GitHubEvent') */
    const DEPLOYMENT_STATUS = 'github.deployment_status';

    /** @Event('\AppBundle\Event\GitHubEvent') */
    const FORK = 'github.fork';

    /** @Event('\AppBundle\Event\GitHubEvent') */
    const GOLLUM = 'github.gollum';

    /** @Event('\AppBundle\Event\GitHubEvent') */
    const ISSUE_COMMENT = 'github.issue_comment';

    /** @Event('\AppBundle\Event\GitHubEvent') */
    const ISSUES = 'github.issues';

    /** @Event('\AppBundle\Event\GitHubEvent') */
    const MEMBER = 'github.member';

    /** @Event('\AppBundle\Event\GitHubEvent') */
    const MEMBERSHIP = 'github.membership';

    /** @Event('\AppBundle\Event\GitHubEvent') */
    const PAGE_BUILD = 'github.page_build';

    /** @Event('\AppBundle\Event\GitHubEvent') */
    const IS_PUBLIC = 'github.public';

    /** @Event('\AppBundle\Event\GitHubEvent') */
    const PR_REVIEW_COMMENT = 'github.pull_request_review_comment';

    /** @Event('\AppBundle\Event\GitHubEvent') */
    const PULL_REQUEST = 'github.pull_request';

    /** @Event('\AppBundle\Event\GitHubEvent') */
    const PUSH = 'github.push';

    /** @Event('\AppBundle\Event\GitHubEvent') */
    const REPOSITORY = 'github.repository';

    /** @Event('\AppBundle\Event\GitHubEvent') */
    const RELEASE = 'github.release';

    /** @Event('\AppBundle\Event\GitHubEvent') */
    const STATUS = 'github.status';

    /** @Event('\AppBundle\Event\GitHubEvent') */
    const TEAM_ADD = 'github.team_add';

    /** @Event('\AppBundle\Event\GitHubEvent') */
    const WATCH = 'github.watch';
}
