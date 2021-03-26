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
            'Cool. Good work.',
            'I like what you have done here.',
            'I like what you have done here. Keep up the good work.',
            'I did a quick review of this PR, I think most things look good.',
            'I had a quick look at this PR, I think it is alright.',
            'Wow, interesting approach.',
            'Cool, it looks like you have quite a talent.',
            'I appreciate you submitting this PR.',
            'Well done! I\'m impressed by this PR.',
            'Nice work here, this makes me happy.',
            'Haha, I was thinking of doing this exact same thing. =)',
            'This is.. this is amazing. Thank you!',
            'Excellent, just like I would have done it.',
            'You, my friend, deserve a BIG HUG for making this PR.',
            'I didn\'t know that I was capable of this emotion. I really really like reviewing this PR. Well done.',
            'I see that more good work is coming your way.',
            'Excellent, keep up the good work.',
            'Two days ago, I was sitting at my usual spot at the top of Big Ben, I was thinking that we really needed this. And now, here you are with a PR. =)',
            'Great work. I told my friends about this PR, they too were impressed.',
        ];

        return $data[array_rand($data)];
    }
}
