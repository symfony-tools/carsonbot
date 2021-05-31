<?php

namespace App\Api\Workflow;

use App\Model\Repository;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
interface WorkflowApi
{
    /**
     * Find workflow runs related to the PR and approve them.
     */
    public function approveWorkflowsForPullRequest(Repository $repository, string $headRepository, string $headBranch): void;
}
