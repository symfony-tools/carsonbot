<?php

namespace App\Subscriber;

use App\Issues\Status;
use App\Issues\StatusApi;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractStatusChangeSubscriber implements EventSubscriberInterface
{
    protected static $triggerWordToStatus = [
        'needs review' => Status::NEEDS_REVIEW,
        'needs work' => Status::NEEDS_WORK,
        'works for me' => Status::WORKS_FOR_ME,
        'reviewed' => Status::REVIEWED,
    ];

    protected $statusApi;

    public function __construct(StatusApi $statusApi)
    {
        $this->statusApi = $statusApi;
    }

    /**
     * Parses the text and looks for keywords to see if this should cause any
     * status change.
     *
     * @param string $body
     *
     * @return null|string
     */
    protected function parseStatusFromText($body)
    {
        $triggerWord = implode('|', array_keys(static::$triggerWordToStatus));
        $formatting = '[\\s\\*]*';
        // Match first character after "status:"
        // Case insensitive ("i"), ignores formatting with "*" before or after the ":"
        $pattern = "~(?=\n|^)${formatting}status${formatting}:${formatting}[\"']?($triggerWord)[\"']?${formatting}[.!]?${formatting}(?<=\r\n|\n|$)~i";

        if (preg_match_all($pattern, $body, $matches)) {
            // Second subpattern = first status character
            return static::$triggerWordToStatus[strtolower(end($matches[1]))];
        }
    }
}

