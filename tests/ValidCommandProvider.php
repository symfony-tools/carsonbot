<?php

namespace App\Tests;

use App\Subscriber\FindReviewerSubscriber;
use App\Subscriber\StatusChangeByCommentSubscriber;

/**
 * To be used in unit tests to make sure subscribers' commands don't conflict.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class ValidCommandProvider
{
    public static function get()
    {
        yield ['Status: needs review', StatusChangeByCommentSubscriber::class];
        yield ['Status: needs work', StatusChangeByCommentSubscriber::class];
        yield ['Status: reviewed', StatusChangeByCommentSubscriber::class];
        yield ['Status: works for me', StatusChangeByCommentSubscriber::class];
        yield ['@carsonbot Status: needs review', StatusChangeByCommentSubscriber::class];
        yield ['@carsonbot Status: reviewed', StatusChangeByCommentSubscriber::class];
        yield ['@carsonbot review', FindReviewerSubscriber::class];
        yield ['@carsonbot reviewer', FindReviewerSubscriber::class];
    }
}
