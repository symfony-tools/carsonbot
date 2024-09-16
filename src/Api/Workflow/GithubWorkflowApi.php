<?php

namespace App\Api\Workflow;

use App\Model\Repository;
use Github\Api\Repository\Actions\WorkflowRuns;
use Github\ResultPager;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class GithubWorkflowApi implements WorkflowApi
{
    public function __construct(
        private readonly ResultPager $resultPager,
        private readonly WorkflowRuns $workflowApi,
    ) {
    }

    public function approveWorkflowsForPullRequest(Repository $repository, string $headRepository, string $headBranch): void
    {
        $runs = $this->resultPager->fetchAllLazy($this->workflowApi, 'all', [$repository->getVendor(), $repository->getName(), [
            'branch' => $headBranch,
            'status' => 'action_required',
        ]]);

        foreach ($runs as $run) {
            if ($headRepository === $run['head_repository']['full_name']) {
                $this->workflowApi->approve($repository->getVendor(), $repository->getName(), $run['id']);
            }
        }
    }
}
