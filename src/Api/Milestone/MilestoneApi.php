<?php

namespace App\Api\Milestone;

use App\Model\Repository;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
interface MilestoneApi
{
    public function updateMilestone(Repository $repository, int $issueNumber, string $milestoneName): void;

    public function exists(Repository $repository, string $milestoneName): bool;
}
