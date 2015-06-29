<?php

namespace AppBundle\GitHub;

class StatusManager
{
    const STATUS_NEEDS_REVIEW = 'needs_review';
    const STATUS_NEEDS_WORK = 'needs_work';
    const STATUS_WORKS_FOR_ME = 'works_for_me';
    const STATUS_REVIEWED = 'reviewed';

    private static $triggerWords = [
        self::STATUS_NEEDS_REVIEW => 'needs review',
        self::STATUS_NEEDS_WORK => 'needs work',
        self::STATUS_WORKS_FOR_ME => 'works for me',
        self::STATUS_REVIEWED => 'reviewed',
    ];

    private static $labels = [
        self::STATUS_NEEDS_REVIEW => 'Status: Needs Review',
        self::STATUS_NEEDS_WORK => 'Status: Needs Work',
        self::STATUS_WORKS_FOR_ME => 'Status: Works for me',
        self::STATUS_REVIEWED => 'Status: Reviewed',
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

        foreach (self::$triggerWords as $status => $triggerWord) {
            // status should be right at the beginning of the string
            if ($triggerWord === strtolower(substr($comment, $statusPosition, strlen($triggerWord)))) {
                return $status;
            }
        }
    }

    /**
     * Returns the name of the label we use on GitHub for a status
     *
     * @param $status
     * @return string
     */
    public static function getLabelForStatus($status)
    {
        if (!isset(self::$labels[$status])) {
            throw new \InvalidArgumentException(sprintf('Invalid status "%s"', $status));
        }

        return self::$labels[$status];
    }

    /**
     * @return array
     */
    public static function getLabelToStatusMap()
    {
        return array_flip(self::$labels);
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
        // Match first character after "status:"
        // Case insensitive ("i"), ignores formatting with "*" before or after the ":"
        $pattern = '~status(\*+:\s*|:[\s\*]*)(\w)~i';

        if (preg_match_all($pattern, $comment, $matches, PREG_OFFSET_CAPTURE)) {
            // Second subpattern = first status character
            $lastMatch = end($matches[2]);

            // [matched string, offset]
            return $lastMatch[1];
        }

        return false;
    }
}
