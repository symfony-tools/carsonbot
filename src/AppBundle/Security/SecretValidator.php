<?php

namespace AppBundle\Security;

use AppBundle\Repository\Repository;
use Symfony\Component\HttpFoundation\Request;

class SecretValidator
{
    /**
     * @return bool
     *
     * @throws \LogicException if not able to check the secret code validity.
     */
    public static function isValid(Request $request, Repository $repository)
    {
        if (null === $repository->getSecret()) {
            return true;
        }
        if (!$request->headers->has('X-HUB-SIGNATURE')) {
            return false;
        }
        if (!extension_loaded('hash')) {
            throw new \LogicException('Missing "hash" extension to check the secret code validity.');
        }

        $hash = $request->headers->get('X-HUB-SIGNATURE');

    	return $hash === 'sha1='.hash_hmac('sha1', $request->getContent(), $repository->getSecret());
    }
}
