<?php

namespace AppBundle\Controller;

use AppBundle\Event\GitHubEvent;
use AppBundle\Exception\GitHubException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class WebhookController extends Controller
{
    /**
     * @Route("/webhooks/github", name="webhooks_github")
     * @Method("POST")
     */
    public function githubAction(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        if (null === $data) {
            throw new GitHubException('Invalid JSON body!');
        }

        $repository = isset($data['repository']['full_name']) ? $data['repository']['full_name'] : null;
        if (empty($repository)) {
            throw new GitHubException('No repository name!');
        }

        $listener = $this->get('app.github.listener_factory')->createFromRepository($repository);

        $dispatcher = $this->get('event_dispatcher');
        $dispatcher->addSubscriber($listener);

        $event = new GitHubEvent($data);
        $eventName = $request->headers->get('X-Github-Event');

        try {
            $dispatcher->dispatch('github.'.$eventName, $event);
        } catch (\Exception $e) {
            throw new GitHubException(sprintf('Failed dispatching "%s" event for "%s" repository.', $eventName, $repository), 0, $e);
        }

        $responseData = $event->getResponseData();

        if (empty($responseData)) {
            $responseData['unsupported_event'] = $eventName;
        }

        return new JsonResponse($responseData);

        // 1 read in what event they have
        // 2 perform some action
        // 3 return JSON

        // log something to the database?
    }
}
