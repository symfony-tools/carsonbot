<?php

namespace App;

/**
 * GitHubEvents.
 *
 * {@link https://developer.github.com/v3/activity/events/types/}
 *
 * @author Jules Pietri <jules@heahprod.com>
 */
final class GitHubEvents
{
    /** @Event('\App\Event\GitHubEvent') */
    const COMMIT_COMMENT = 'github.commit_comment';

    /** @Event('\App\Event\GitHubEvent') */
    const CREATE = 'github.create';

    /** @Event('\App\Event\GitHubEvent') */
    const DELETE = 'github.delete';

    /** @Event('\App\Event\GitHubEvent') */
    const DEPLOYMENT = 'github.deployment';

    /** @Event('\App\Event\GitHubEvent') */
    const DEPLOYMENT_STATUS = 'github.deployment_status';

    /** @Event('\App\Event\GitHubEvent') */
    const FORK = 'github.fork';

    /** @Event('\App\Event\GitHubEvent') */
    const GOLLUM = 'github.gollum';

    /** @Event('\App\Event\GitHubEvent') */
    const ISSUE_COMMENT = 'github.issue_comment';

    /** @Event('\App\Event\GitHubEvent') */
    const ISSUES = 'github.issues';

    /** @Event('\App\Event\GitHubEvent') */
    const MEMBER = 'github.member';

    /** @Event('\App\Event\GitHubEvent') */
    const MEMBERSHIP = 'github.membership';

    /** @Event('\App\Event\GitHubEvent') */
    const PAGE_BUILD = 'github.page_build';

    /** @Event('\App\Event\GitHubEvent') */
    const IS_PUBLIC = 'github.public';

    /** @Event('\App\Event\GitHubEvent') */
    const PR_REVIEW_COMMENT = 'github.pull_request_review_comment';

    /** @Event('\App\Event\GithubEvent') */
    const PULL_REQUEST_REVIEW = 'github.pull_request_review';

    /** @Event('\App\Event\GitHubEvent') */
    const PULL_REQUEST = 'github.pull_request';

    /** @Event('\App\Event\GitHubEvent') */
    const PUSH = 'github.push';

    /** @Event('\App\Event\GitHubEvent') */
    const REPOSITORY = 'github.repository';

    /** @Event('\App\Event\GitHubEvent') */
    const RELEASE = 'github.release';

    /** @Event('\App\Event\GitHubEvent') */
    const STATUS = 'github.status';

    /** @Event('\App\Event\GitHubEvent') */
    const TEAM_ADD = 'github.team_add';

    /** @Event('\App\Event\GitHubEvent') */
    const WATCH = 'github.watch';
}
