<?php

declare(strict_types=1);

namespace App\Api\Milestone;

use App\Model\Repository;

class NullMilestoneApi implements MilestoneApi
{
    public function updateMilestone(Repository $repository, int $issueNumber, string $milestoneName)
    {
    }

    public function exists(Repository $repository, string $milestoneName): bool
    {
        return false;
    }
}
