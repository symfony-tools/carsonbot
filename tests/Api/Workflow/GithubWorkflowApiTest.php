<?php

namespace App\Tests\Api\Workflow;

use App\Api\Workflow\GithubWorkflowApi;
use App\Model\Repository;
use Github\Api\Repository\Actions\WorkflowRuns;
use Github\ResultPager;
use PHPUnit\Framework\TestCase;

class GithubWorkflowApiTest extends TestCase
{
    public function testApproveWorkflowsForPullRequest()
    {
        $workflowRuns = $this->createMock(WorkflowRuns::class);
        $resultPager = $this->createMock(ResultPager::class);
        $resultPager->expects($this->once())
            ->method('fetchAllLazy')
            ->with($workflowRuns, 'all', ['carsonbot-playground', 'symfony', ['branch' => 'patch-1', 'status' => 'action_required']])
            ->willReturn((static function () {
                yield 'total_count' => 2;
                yield 'workflow_runs' => [
                    ['id' => 123, 'head_repository' => ['full_name' => 'contributor/symfony']],
                    ['id' => 456, 'head_repository' => ['full_name' => 'someone-else/symfony']],
                ];
            })());

        $workflowRuns->expects($this->once())
            ->method('approve')
            ->with('carsonbot-playground', 'symfony', 123);

        $api = new GithubWorkflowApi($resultPager, $workflowRuns);
        $api->approveWorkflowsForPullRequest(new Repository('carsonbot-playground', 'symfony', null), 'contributor/symfony', 'patch-1');
    }
}
