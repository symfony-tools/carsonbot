<?php

namespace App\Api\Status;

/**
 * The possible statuses of an issue.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
final class Status
{
    public const NEEDS_REVIEW = 'needs_review';

    public const NEEDS_WORK = 'needs_work';

    public const WORKS_FOR_ME = 'works_for_me';

    public const REVIEWED = 'reviewed';
}
