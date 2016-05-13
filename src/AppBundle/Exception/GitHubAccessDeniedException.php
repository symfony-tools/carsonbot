<?php

namespace AppBundle\Exception;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * GitHubAccessDeniedException.
 *
 * @author Jules Pietri <jules@heahprod.com>
 */
class GitHubAccessDeniedException extends AccessDeniedHttpException implements GitHubExceptionInterface
{
}
