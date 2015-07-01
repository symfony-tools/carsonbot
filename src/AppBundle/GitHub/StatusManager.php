<?php

namespace AppBundle\GitHub;

class StatusManager
{
    const STATUS_NEEDS_REVIEW = 'needs_review';
    const STATUS_NEEDS_WORK = 'needs_work';
    const STATUS_WORKS_FOR_ME = 'works_for_me';
    const STATUS_REVIEWED = 'reviewed';

    private static $triggerWords = [
        'needs review' => self::STATUS_NEEDS_REVIEW,
        'needs work' => self::STATUS_NEEDS_WORK,
        'works for me' => self::STATUS_WORKS_FOR_ME,
        'reviewed' => self::STATUS_REVIEWED,
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
        $triggerWord = implode('|', array_keys(self::$triggerWords));
        $formatting = '[\\s\\*]*';

        // Match first character after "status:"
        // Case insensitive ("i"), ignores formatting with "*" before or after the ":"
        $pattern = "~(?=\n|^)${formatting}status${formatting}:${formatting}[\"']?($triggerWord)[\"']?${formatting}[.!]?${formatting}(?<=\r\n|\n|$)~i";

        if (preg_match_all($pattern, $comment, $matches)) {
            // Second subpattern = first status character
            return self::$triggerWords[strtolower(end($matches[1]))];
        }

        return null;
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
}
