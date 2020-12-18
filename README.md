The Carson Issue Butler
=======================

![CI](https://github.com/carsonbot/carsonbot/workflows/CI/badge.svg)

Carson is a bot that currently runs on the [symfony/symfony](https://github.com/symfony/symfony)
repository. His job is to help automate different issue and pull request
workflows.

For an introduction, read: http://symfony.com/blog/calling-for-issue-triagers-a-new-workflow-and-the-carson-butler

Currently, Carson's super powers are used to automatically label issues based
on comments from people in the community. This gives anyone the power to review
an issue or pull request and comment to update its status. If Carson does his job
well (i.e. if it's useful), more features may be added in the future.

For details on how this review / label process works, see http://symfony.com/doc/current/contributing/community/reviews.html

## Feature scope

Carson is excellent to look at issues and pull request to make sure they are correctly
labeled, uses a supported branch or to say hi to new contributors. Carson also likes
cloning a repository and run checks on it time to time, ie making sure all translations
are up to date.

Carson will never read the contents of a pull requests, ie it never parses the code
to check for typos or other automated fixes.

## Features

Here is all the things Carson can help you with. Not all features are enabled. A
list of enabled features for a specific repository is defined in `config/services.yaml`.

### Automatic labeling from content

Using the issue template, Carson adds labels "Bug", "Feature", "BC Break"
and "Deprecation" on issues and pull requests.

### Add "Needs Review" label on pull requests

When a PR is opened, then Carson will add "Needs Review".

### Add "Needs Review" label on bugs issues

If an issue is labeled with "Bug", then Carson will add "Needs Review".

### Update pull request title with component labels

When a PR is labeled, Carson looks for component labels (i.e. labels with color #dddddd).
These labels' names will be added to the PR title (e.g. `[HttpKernel]`).

### Close draft pull requests

When a PR is open as "draft", Carson adds a comment to encourage people to mark it as
"ready to review". If no action is taken, Carson will close the PR in one hour.

### Add milestone to PRs

When a new PR is opened and it does not target the default branch or current version, then
Carson will update the milestone of the PR to a existing milestone that matches the target branch.

### Manage pull request status

The "status" of a pull request defined by one of the labels: "Needs review", "Needs work",
"Works for me" and "Reviewed". The status can be changed by adding a comment like:
"Status: needs work".

The status will also be changed if someone adds a review that requests changes or
approves the PR. Finally, the status will be "Needs review" after the author pushes
changes to the PR.

### Welcome new contributors

When a user opens their first PR, they will get a welcome message explaining the
review process and how to increase the chances to get the PR merged.

### Comment on stale issues

Carson will look for old inactive issues and start a process with them.

1. Bot will make a comment to encourage activity and add label "Stalled".
1. Bot will make a comment to inform the issue will be closed
1. Bot will close the issue.

The process can be interrupted with anyone making a comment on the issue or the
"Keep open" label is added.

### Suggest reviewers

Carson can look at the PR to figure out who will be a good candidate to review the
changes. This can be triggered by someone can make a comment in a PR to say
`@carsonbot find me a reviewer please` (or really anything with `@carsonbot` and
`review` on the same line).

Carson will also try to find a reviewer if there is no activity on a new PR within
the first 20 hours.

### Add a warning if pull request target unsupported branch

If a PR is opened towards a branch that is not maintained anymore, Carson will
kindly explain to the author what to do.

### Open issues when docs for config reference is incomplete

The Symfony documentation includes some pages with "configuration reference", to
make sure these are always up to date, Carson will look at the bundles' defined
config and compare that to what is documented. If Carson finds something that is
missing, it will open an issue in the symfony/symfony-docs repository.
