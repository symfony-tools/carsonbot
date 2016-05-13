<?php

namespace AppBundle\Exception;

use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

/**
 * GitHubPreconditionFailedException.
 *
 * @author Jules Pietri <jules@heahprod.com>
 */
class GitHubPreconditionFailedException extends PreconditionFailedHttpException implements GitHubExceptionInterface
{
}
