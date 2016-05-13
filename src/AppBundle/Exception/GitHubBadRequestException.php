<?php

namespace AppBundle\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * GitHubBadRequestException.
 *
 * @author Jules Pietri <jules@heahprod.com>
 */
class GitHubBadRequestException extends BadRequestHttpException implements GitHubExceptionInterface
{
}
