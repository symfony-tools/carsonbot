<?php

namespace AppBundle\GitHub;

class StatusManager
{
    const STATUS_NEEDS_REVIEW = 'needs_review';
    const STATUS_NEEDS_WORK = 'needs_work';
    const STATUS_WORKS_FOR_ME = 'works_for_me';
    const STATUS_REVIEWED = 'reviewed';

    private static $triggerWords = [
        self::STATUS_NEEDS_REVIEW => ['needs review'],
        self::STATUS_NEEDS_WORK => ['needs work'],
        self::STATUS_WORKS_FOR_ME => ['works for me'],
        self::STATUS_REVIEWED => ['reviewed'],
    ];

    /**
     * Parses the text of the comment and looks for keywords to see
     * if this should cause any status change.
     *
     * Returns the status that this comment is causing or null of there
     * should be no status change.
     *
     * @param $comment
     * @return string|null
     */
    public function getStatusChangeFromComment($comment)
    {
        // 1) Find the last "status:"
        $statusPosition = $this->findStatusPosition($comment);

        if ($statusPosition === false) {
            return null;
        }

        // get what comes *after* status:, with spaces trimmed
        // now, the status string "needs review" should be at the 0 character
        $statusString = trim(substr($comment, $statusPosition));

        $newStatus = null;
        foreach (self::$triggerWords as $status => $triggerWords) {
            foreach ($triggerWords as $triggerWord) {
                // status should be right at the beginning of the string
                if (stripos($statusString, $triggerWord) === 0) {
                    // don't return immediately - we use the last status
                    // in the rare case there are multiple
                    $newStatus = $status;
                }
            }
        }

        return $newStatus;
    }

    /**
     * Finds the position where the status string will start - e.g.
     * for "Status: Needs review", this would return the position
     * that points to the "N" in "Needs".
     *
     * If there are multiple "Status:" in the string, this returns the
     * final one.
     *
     * This takes into account possible formatting (e.g. **Status**: )
     *
     * Returns the position or false if none was found.
     *
     * @param string $comment
     * @return boolean|integer
     */
    private function findStatusPosition($comment)
    {
        $formats = ['status:', '*status*:', '**status**:'];

        foreach ($formats as $format) {
            $lastStatusPosition = strripos($comment, $format);

            if ($lastStatusPosition !== false) {
                return $lastStatusPosition + strlen($format);
            }
        }

        return false;
    }
}
