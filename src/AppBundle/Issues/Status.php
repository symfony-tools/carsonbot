<?php

namespace AppBundle\Issues;

/**
 * The possible statuses of an issue.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
final class Status
{
    const NEEDS_COMMENTS = 'needs_comments';

    const NEEDS_REVIEW = 'needs_review';

    const NEEDS_WORK = 'needs_work';

    const WORKS_FOR_ME = 'works_for_me';

    const REVIEWED = 'reviewed';

    const READY = 'ready';

    const IN_PROGRESS = 'in_progress';

    const FINISHED = 'finished';

    const ON_HOLD = 'on_hold';
}
