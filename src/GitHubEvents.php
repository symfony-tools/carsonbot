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
    public const COMMIT_COMMENT = 'github.commit_comment';

    /** @Event('\App\Event\GitHubEvent') */
    public const CREATE = 'github.create';

    /** @Event('\App\Event\GitHubEvent') */
    public const DELETE = 'github.delete';

    /** @Event('\App\Event\GitHubEvent') */
    public const DEPLOYMENT = 'github.deployment';

    /** @Event('\App\Event\GitHubEvent') */
    public const DEPLOYMENT_STATUS = 'github.deployment_status';

    /** @Event('\App\Event\GitHubEvent') */
    public const FORK = 'github.fork';

    /** @Event('\App\Event\GitHubEvent') */
    public const GOLLUM = 'github.gollum';

    /** @Event('\App\Event\GitHubEvent') */
    public const ISSUE_COMMENT = 'github.issue_comment';

    /** @Event('\App\Event\GitHubEvent') */
    public const ISSUES = 'github.issues';

    /** @Event('\App\Event\GitHubEvent') */
    public const MEMBER = 'github.member';

    /** @Event('\App\Event\GitHubEvent') */
    public const MEMBERSHIP = 'github.membership';

    /** @Event('\App\Event\GitHubEvent') */
    public const PAGE_BUILD = 'github.page_build';

    /** @Event('\App\Event\GitHubEvent') */
    public const IS_PUBLIC = 'github.public';

    /** @Event('\App\Event\GitHubEvent') */
    public const PR_REVIEW_COMMENT = 'github.pull_request_review_comment';

    /** @Event('\App\Event\GithubEvent') */
    public const PULL_REQUEST_REVIEW = 'github.pull_request_review';

    /** @Event('\App\Event\GitHubEvent') */
    public const PULL_REQUEST = 'github.pull_request';

    /** @Event('\App\Event\GitHubEvent') */
    public const PUSH = 'github.push';

    /** @Event('\App\Event\GitHubEvent') */
    public const REPOSITORY = 'github.repository';

    /** @Event('\App\Event\GitHubEvent') */
    public const RELEASE = 'github.release';

    /** @Event('\App\Event\GitHubEvent') */
    public const STATUS = 'github.status';

    /** @Event('\App\Event\GitHubEvent') */
    public const TEAM_ADD = 'github.team_add';

    /** @Event('\App\Event\GitHubEvent') */
    public const WATCH = 'github.watch';
}
