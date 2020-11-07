<?php

declare(strict_types=1);

namespace App\Api\Milestone;

use App\Model\Repository;

/**
 * Dont fetch data from external source.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class StaticMilestoneApi extends NullMilestoneApi
{
    public function exists(Repository $repository, string $milestoneName): bool
    {
        return in_array($milestoneName, ['3.4', '4.4', '5.1', '5.x']);
    }
}
