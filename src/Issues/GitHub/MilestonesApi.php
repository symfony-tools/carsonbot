<?php

namespace App\Issues\GitHub;

use App\Repository\Repository;
use Github\Api\Issue;
use Github\Api\Issue\Labels;
use Github\Api\Issue\Milestones;

/**
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class MilestonesApi
{
    /**
     * @var Milestones
     */
    private $milestonesApi;

    /**
     * @var Issue
     */
    private $issuesApi;

    /**
     * @var string[][]
     */
    private $cache = [];

    public function __construct(Milestones $milestonesApi, Issue $issuesApi)
    {
        $this->milestonesApi = $milestonesApi;
        $this->issuesApi = $issuesApi;
    }

    private function getMilestones(Repository $repository)
    {
        $key = $this->getCacheKey($repository);
        if (!isset($this->cache[$key])) {
            $this->cache[$key] = [];

            $milestones = $this->milestonesApi->all($repository->getVendor(), $repository->getName());

            foreach ($milestones as $milestone) {
                $this->cache[$key][] = $milestone;
            }
        }

        return $this->cache[$key];
    }

    public function updateMilestone(Repository $repository, int $issueNumber,  string $milestoneName)
    {
        $milestoneNumber = null;
        foreach ($this->getMilestones($repository) as $milestone) {
            if ($milestone['name'] === $milestoneName) {
                $milestoneNumber = $milestone['number'];
            }
        }

        if ($milestoneNumber === null) {
            throw new \LogicException(\sprintf('Milestone "%s" does not exist', $milestoneName));
        }

        $this->issuesApi->update($repository->getVendor(), $repository->getName(), $issueNumber, [
            'milestone' => $milestoneNumber,
        ]);
    }

    /**
     * @return bool
     */
    public function exits(Repository $repository, string $milestoneName)
    {
        foreach ($this->getMilestones($repository) as $milestone) {
            if ($milestone['name'] === $milestoneName) {
                return true;
            }
        }

        return false;
    }

    private function getCacheKey(Repository $repository)
    {
        return sprintf('%s_%s', $repository->getVendor(), $repository->getName());
    }
}
