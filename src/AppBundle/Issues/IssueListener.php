<?php

namespace AppBundle\Issues;

class IssueListener
{
    private static $triggerWordToStatus = [
        'needs review' => Status::NEEDS_REVIEW,
        'needs work' => Status::NEEDS_WORK,
        'works for me' => Status::WORKS_FOR_ME,
        'reviewed' => Status::REVIEWED,
    ];

    /**
     * @var StatusApi
     */
    private $statusApi;

    public function __construct(StatusApi $statusApi)
    {
        $this->statusApi = $statusApi;
    }

    /**
     * Parses the text of the comment and looks for keywords to see
     * if this should cause any status change.
     *
     * Returns the status that this comment is causing or null of there
     * should be no status change.
     *
     * @param int    $issueNumber The issue number
     * @param string $comment     The text of the comment
     *
     * @return null|string The status that the issue was moved to or null
     */
    public function handleCommentAddedEvent($issueNumber, $comment)
    {
        $triggerWord = implode('|', array_keys(self::$triggerWordToStatus));
        $formatting = '[\\s\\*]*';

        // Match first character after "status:"
        // Case insensitive ("i"), ignores formatting with "*" before or after the ":"
        $pattern = "~(?=\n|^)${formatting}status${formatting}:${formatting}[\"']?($triggerWord)[\"']?${formatting}[.!]?${formatting}(?<=\r\n|\n|$)~i";

        if (preg_match_all($pattern, $comment, $matches)) {
            // Second subpattern = first status character
            $newStatus = self::$triggerWordToStatus[strtolower(end($matches[1]))];

            $this->statusApi->setIssueStatus($issueNumber, $newStatus);

            return $newStatus;
        }

        return;
    }

    /**
     * Adds a "Needs Review" label to new PRs.
     *
     * @param int $prNumber The number of the PR
     * @param int $prTitle  The title of the PR
     * @param int $prBody   The full text description of the PR
     *
     * @return string The new status
     */
    public function handlePullRequestCreatedEvent($prNumber, $prTitle, $prBody)
    {
        $prLabels = array();

        // new PRs always require review
        $newStatus = Status::NEEDS_REVIEW;
        $prLabels[] = $newStatus;

        // the PR title usually indicates the affected component
        if (preg_match('/^\[(?P<component>.*)\] .*/$', $prTitle, $matches)) {
            $prLabels[] = $matches['component'];
        }

        // the PR body usually indicates if this is a Bug, Feature, BC Break or Deprecation
        if (preg_match('/^\|\s*Bug fix?\s*\|\s*yes\s*$/', $prBody, $matches)) {
            $prLabels[] = 'Bug';
        }
        if (preg_match('/^\|\s*New feature?\s*\|\s*yes\s*$/', $prBody, $matches)) {
            $prLabels[] = 'Feature';
        }
        if (preg_match('/^\|\s*BC breaks?\s*\|\s*yes\s*$/', $prBody, $matches)) {
            $prLabels[] = 'BC Break';
        }
        if (preg_match('/^\|\s*Deprecations?\s*\|\s*yes\s*$/', $prBody, $matches)) {
            $prLabels[] = 'Deprecation';
        }

        $this->statusApi->setIssueLabels($prNumber, $prLabels);

        return $newStatus;
    }

    /**
     * Changes "Bug" issues to "Needs Review".
     *
     * @param int    $issueNumber The issue that was labeled
     * @param string $label       The added label
     *
     * @return null|string The status that the issue was moved to or null
     */
    public function handleLabelAddedEvent($issueNumber, $label)
    {
        // Ignore non-bugs
        if ('bug' !== strtolower($label)) {
            return;
        }

        $currentStatus = $this->statusApi->getIssueStatus($issueNumber);

        // Ignore if the issue already has a status
        if (null !== $currentStatus) {
            return;
        }

        $newStatus = Status::NEEDS_REVIEW;

        $this->statusApi->setIssueStatus($issueNumber, $newStatus);

        return $newStatus;
    }
}
