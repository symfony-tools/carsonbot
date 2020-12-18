<?php

declare(strict_types=1);

namespace App\Service;

use App\Api\Issue\IssueType;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class StaleIssueCommentGenerator
{
    /**
     * Get a comment to say: "I will close this soon".
     */
    public function getInformAboutClosingComment(): string
    {
        $messages = [
            'Hello? This issue is about to be closed if nobody replies.',
            'Friendly ping? Should this still be open? I will close if I don\'t hear anything.',
            'Could I get a reply or should I close this?',
            'Just a quick reminder to make a comment on this. If I don\'t hear anything I\'ll close this.',
            'Friendly reminder that this issue exists. If I don\'t hear anything I\'ll close this.',
            'Could I get an answer? If I do not hear anything I will assume this issue is resolved or abandoned. Please get back to me <3',
        ];

        return $messages[array_rand($messages)];
    }

    /**
     * Get a comment to say: "I'm closing this now".
     */
    public function getClosingComment(): string
    {
        return <<<TXT
Hey,

I didn't hear anything so I'm going to close it. Feel free to comment if this is still relevant, I can always reopen!
TXT;
    }

    /**
     * Get a comment that encourage users to reply or close the issue themselves.
     *
     * @param string $type Valid types are IssueType::*
     */
    public function getComment(string $type): string
    {
        switch ($type) {
            case IssueType::BUG:
                return $this->bug();
            case IssueType::FEATURE:
            case IssueType::RFC:
                return $this->feature();
            default:
                return $this->unknown();
        }
    }

    private function bug(): string
    {
        return <<<TXT
Hey, thanks for your report!
There has not been a lot of activity here for a while. Is this bug still relevant? Have you managed to find a workaround?
TXT;
    }

    private function feature(): string
    {
        return <<<TXT
Thank you for this suggestion.
There has not been a lot of activity here for a while. Would you still like to see this feature?
TXT;
    }

    private function unknown(): string
    {
        return <<<TXT
Thank you for this issue.
There has not been a lot of activity here for a while. Has this been resolved?
TXT;
    }
}
