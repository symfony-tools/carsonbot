<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Listener;

use AppBundle\Exception\GitHubExceptionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * GitHubExceptionListener.
 *
 * @author Jules Pietri <jules@heahprod.com>
 */
class GitHubExceptionListener implements EventSubscriberInterface
{
    private $debug;

    public function __construct($debug)
    {
        $this->debug = $debug;
    }

    public function onGitHubException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        if (!$exception instanceof GitHubExceptionInterface) {
            return;
        }

        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
        if (!$this->debug && !$exception instanceof HttpExceptionInterface) {
            $message = 'Internal error';
        } else {
            $message = $exception->getMessage();
        }
        if ($this->debug && $previous = $exception->getPrevious()) {
            $message .= ' => '.$previous->getMessage();
        }

        $event->setResponse(new JsonResponse(array('error' => $message), $statusCode));
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::EXCEPTION => 'onGitHubException',
        );
    }
}
