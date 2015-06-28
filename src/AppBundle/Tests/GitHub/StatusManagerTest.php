<?php

namespace AppBundle\Tests\GitHub;

use AppBundle\GitHub\StatusManager;

class StatusManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getCommentsForStatusChange
     */
    public function testGetStatusChangeFromComment($comment, $expectedStatus)
    {
        $statusManager = new StatusManager();
        $actualStatus = $statusManager->getStatusChangeFromComment($comment);

        $this->assertEquals(
            $expectedStatus,
            $actualStatus,
            sprintf('Comment "%s" did not result in the status "%s"', $comment, $expectedStatus)
        );
    }

    public function getCommentsForStatusChange()
    {
        $tests = [];
        $tests[] = array(
            'Have a great day!',
            null
        );
        // basic tests for status change
        $tests[] = array(
            'Status: needs review',
            StatusManager::STATUS_NEEDS_REVIEW
        );
        $tests[] = array(
            'Status: needs work',
            StatusManager::STATUS_NEEDS_WORK
        );
        $tests[] = array(
            'Status: works for me!',
            StatusManager::STATUS_WORKS_FOR_ME
        );
        $tests[] = array(
            'Status: reviewed',
            StatusManager::STATUS_REVIEWED
        );

        // play with different formatting
        $tests[] = array(
            'STATUS: REVIEWED',
            StatusManager::STATUS_REVIEWED
        );
        $tests = [];
        $tests[] = array(
            '**Status**: reviewed',
            StatusManager::STATUS_REVIEWED
        );
        return $tests;
        // missing the colon - so we do NOT read this
        $tests[] = array(
            'Status reviewed',
            null,
        );
        $tests[] = array(
            'Status:reviewed',
            StatusManager::STATUS_REVIEWED
        );
        $tests[] = array(
            'Status:     reviewed',
            StatusManager::STATUS_REVIEWED
        );

        // multiple matches - use the last one
        $tests[] = array(
            "Status: needs review \r\n that is what the issue *was* marked as. Now it should be Status: reviewed",
            StatusManager::STATUS_REVIEWED
        );
        // "needs review" does not come directly after status: , so there is no status change
        $tests[] = array(
            'Here is my status: I\'m really happy! I realize this needs review, but I\'m, having too much fun Googling cats!',
            null
        );

        return $tests;
    }
}
