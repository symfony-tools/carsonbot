<?php

namespace App\Api\Workflow;

use App\Model\Repository;

class NullWorkflowApi implements WorkflowApi
{
    public function approveWorkflowsForPullRequest(Repository $repository, string $headRepository, string $headBranch): void
    {
    }
}
