<?php

namespace App\Service;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class WipParser
{
    /**
     * Returns true if the title starts with [WIP], (WIP) or WIP:.
     */
    public static function matchTitle(string $title): bool
    {
        return (bool) preg_match('@^(\[wip\]|\(wip\)|wip:)@mi', $title);
    }
}
