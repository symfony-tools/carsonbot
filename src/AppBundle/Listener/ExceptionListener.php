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

use AppBundle\Exception\GitHubException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * ExceptionListener.
 *
 * @author Jules Pietri <jules@heahprod.com>
 */
class ExceptionListener implements EventSubscriberInterface
{
    public function onGitHubException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        if (!$exception instanceof GitHubException) {
            return;
        }

        $message = $exception->getMessage();
        if ($previous = $exception->getPrevious()) {
            $message .= ' => '.$previous->getMessage();
        }

        $event->setResponse(new JsonResponse(array('error' => $message), 500));
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
