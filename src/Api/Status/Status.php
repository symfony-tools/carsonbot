<?php

namespace App\Api\Status;

/**
 * The possible statuses of an issue.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
final class Status
{
    public const string NEEDS_REVIEW = 'needs_review';
    public const string NEEDS_WORK = 'needs_work';
    public const string WORKS_FOR_ME = 'works_for_me';
    public const string REVIEWED = 'reviewed';
}
