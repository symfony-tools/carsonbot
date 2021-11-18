<?php

declare(strict_types=1);

namespace App\Service;

use App\Api\Issue\IssueApi;
use App\Model\Repository;

/**
 * From a large list of possible reviewers, reduce it to just one.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class ReviewerFilter
{
    private $issueApi;

    // These users will never be pinged for a review
    private $blocked = [
            'fabpot', 'tobion', 'nicolas-grekas', 'stof', 'dunglas',
            'xabbuh', 'javiereguiluz', 'lyrixx', 'weaverryan', 'chalasr', 'ogizanagi',
            'yceruto', 'nyholm', 'wouterj', 'derrabus', 'jderusse',
            'oskarstark', 'kbond', 'welcomattic', 'gromnan', 'fancyweb',
        ];

    public function __construct(IssueApi $issueApi)
    {
        $this->issueApi = $issueApi;
    }

    public function suggestReviewer(array $possibleReviewers, Repository $repository, $pullRequestNumber): ?string
    {
        // Dont suggest block listed
        $possibleReviewers = $this->filterBlocked($possibleReviewers);
        $possibleReviewers = $this->filterUsersInvolved($possibleReviewers, $repository, $pullRequestNumber);

        foreach ($possibleReviewers as $reviewer) {
            return $reviewer['username'];
        }

        return null;
    }

    /**
     * Dont get people involved in the PR already.
     */
    private function filterUsersInvolved(array $possibleReviewers, Repository $repository, $pullRequestNumber): array
    {
        $output = [];
        if (empty($possibleReviewers)) {
            return $output;
        }

        $users = $this->issueApi->getUsers($repository, $pullRequestNumber);
        foreach ($possibleReviewers as $reviewer) {
            $username = strtolower($reviewer['username']);
            if (in_array($username, $users) || in_array($reviewer['email'] ?? '', $users)) {
                continue;
            }

            $output[] = $reviewer;
        }

        return $output;
    }

    private function filterBlocked(array $possibleReviewers): array
    {
        $output = [];

        foreach ($possibleReviewers as $reviewer) {
            if (empty($reviewer['username'])) {
                continue;
            }

            $username = strtolower($reviewer['username']);
            if (in_array($username, $this->blocked)) {
                continue;
            }

            $output[] = $reviewer;
        }

        return $output;
    }
}
