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
