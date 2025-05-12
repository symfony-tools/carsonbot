<?php

namespace App\Subscriber;

use App\Api\Status\Status;
use App\Api\Status\StatusApi;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractStatusChangeSubscriber implements EventSubscriberInterface
{
    /**
     * @var array<string, string>
     */
    protected static array $triggerWordToStatus = [
        'needs review' => Status::NEEDS_REVIEW,
        'needs work' => Status::NEEDS_WORK,
        'works for me' => Status::WORKS_FOR_ME,
        'reviewed' => Status::REVIEWED,
    ];

    public function __construct(
        protected StatusApi $statusApi,
    ) {
    }

    /**
     * Parses the text and looks for keywords to see if this should cause any
     * status change.
     */
    protected function parseStatusFromText(string $body): ?string
    {
        $triggerWord = implode('|', array_keys(static::$triggerWordToStatus));
        $formatting = '[\\s\\*]*';
        // Match first character after "status:"
        // Case insensitive ("i"), ignores formatting with "*" before or after the ":"
        $pattern = "~(?=\n|^)(?:\@carsonbot)?{$formatting}status{$formatting}:{$formatting}[\"']?($triggerWord)[\"']?{$formatting}[.!]?{$formatting}(?<=\r\n|\n|$)~i";

        if (preg_match_all($pattern, $body, $matches)) {
            // Second subpattern = first status character
            return static::$triggerWordToStatus[strtolower(end($matches[1]) ?: '')];
        }

        return null;
    }
}
