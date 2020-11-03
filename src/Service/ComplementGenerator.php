<?php

namespace App\Service;

/**
 * A small class that generates a complement.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class ComplementGenerator
{
    /**
     * @return string
     */
    public function getPullRequestComplement()
    {
        $data = [
            'Good work.',
            'I like what you have done here.',
            'I did a quick review of this PR, I think most things looks good',
            'Wow, interesting approach',
            'Cool, it looks like you have quite a talent',
            'I appreciate you submitting this PR.',
            'Well done!, Im impressed by this PR.',
            'Nice work here, I this makes me happy.',
            'Haha, I was thinking of doing this exact same thing. =)',
            'Excellent, just like I would have done it.',
        ];

        return $data[array_rand($data)];
    }
}
