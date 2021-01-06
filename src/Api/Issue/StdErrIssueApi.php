<?php

namespace App\Api\Issue;

use App\Model\Repository;

/**
 * This will not write to Github, only output to STDERR. But it will try to read from Github.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class StdErrIssueApi extends GithubIssueApi
{
    public function open(Repository $repository, string $title, string $body, array $labels)
    {
        error_log(sprintf('Open new issue on %s', $repository->getFullName()));
        error_log('Title: '.$title);
        error_log('Labels: '.json_encode($labels));
        error_log($body);
    }

    public function close(Repository $repository, $issueNumber)
    {
        error_log(sprintf('Closing %s#%d', $repository->getFullName(), $issueNumber));
    }

    public function commentOnIssue(Repository $repository, $issueNumber, string $commentBody)
    {
        error_log(sprintf('Commenting on %s#%d', $repository->getFullName(), $issueNumber));
        error_log($commentBody);
    }
}
