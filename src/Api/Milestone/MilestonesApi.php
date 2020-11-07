<?php

namespace App\Api\Milestone;

use App\Model\Repository;
use Github\Api\Issue;
use Github\Api\Issue\Milestones;

/**
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

    public function updateMilestone(Repository $repository, int $issueNumber, string $milestoneName)
    {
        $milestoneNumber = null;
        foreach ($this->getMilestones($repository) as $milestone) {
            if ($milestone['title'] === $milestoneName) {
                $milestoneNumber = $milestone['number'];
            }
        }

        if (null === $milestoneNumber) {
            throw new \LogicException(\sprintf('Milestone "%s" does not exist', $milestoneName));
        }

        $this->issuesApi->update($repository->getVendor(), $repository->getName(), $issueNumber, [
            'milestone' => $milestoneNumber,
        ]);
    }

    /**
     * @return bool
     */
    public function exists(Repository $repository, string $milestoneName)
    {
        foreach ($this->getMilestones($repository) as $milestone) {
            if ($milestone['title'] === $milestoneName) {
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
