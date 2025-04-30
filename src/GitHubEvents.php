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
    /** @Event("\App\Event\GitHubEvent") */
    public const string COMMIT_COMMENT = 'github.commit_comment';

    /** @Event("\App\Event\GitHubEvent") */
    public const string CREATE = 'github.create';

    /** @Event("\App\Event\GitHubEvent") */
    public const string DELETE = 'github.delete';

    /** @Event("\App\Event\GitHubEvent") */
    public const string DEPLOYMENT = 'github.deployment';

    /** @Event("\App\Event\GitHubEvent") */
    public const string DEPLOYMENT_STATUS = 'github.deployment_status';

    /** @Event("\App\Event\GitHubEvent") */
    public const string FORK = 'github.fork';

    /** @Event("\App\Event\GitHubEvent") */
    public const string GOLLUM = 'github.gollum';

    /** @Event("\App\Event\GitHubEvent") */
    public const string ISSUE_COMMENT = 'github.issue_comment';

    /** @Event("\App\Event\GitHubEvent") */
    public const string ISSUES = 'github.issues';

    /** @Event("\App\Event\GitHubEvent") */
    public const string MEMBER = 'github.member';

    /** @Event("\App\Event\GitHubEvent") */
    public const string MEMBERSHIP = 'github.membership';

    /** @Event("\App\Event\GitHubEvent") */
    public const string PAGE_BUILD = 'github.page_build';

    /** @Event("\App\Event\GitHubEvent") */
    public const string IS_PUBLIC = 'github.public';

    /** @Event("\App\Event\GitHubEvent") */
    public const string PR_REVIEW_COMMENT = 'github.pull_request_review_comment';

    /** @Event("\App\Event\GitHubEvent") */
    public const string PULL_REQUEST_REVIEW = 'github.pull_request_review';

    /** @Event("\App\Event\GitHubEvent") */
    public const string PULL_REQUEST = 'github.pull_request';

    /** @Event("\App\Event\GitHubEvent") */
    public const string PUSH = 'github.push';

    /** @Event("\App\Event\GitHubEvent") */
    public const string REPOSITORY = 'github.repository';

    /** @Event("\App\Event\GitHubEvent") */
    public const string RELEASE = 'github.release';

    /** @Event("\App\Event\GitHubEvent") */
    public const string STATUS = 'github.status';

    /** @Event("\App\Event\GitHubEvent") */
    public const string TEAM_ADD = 'github.team_add';

    /** @Event("\App\Event\GitHubEvent") */
    public const string WATCH = 'github.watch';
}
