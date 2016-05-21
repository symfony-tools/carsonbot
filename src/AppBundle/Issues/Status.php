<?php

namespace AppBundle\Issues;

/**
 * The possible statuses of an issue.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
final class Status
{
    const NEEDS_REVIEW = 'needs_review';

    const NEEDS_WORK = 'needs_work';

    const WORKS_FOR_ME = 'works_for_me';

    const REVIEWED = 'reviewed';
}
