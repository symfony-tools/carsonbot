<?php

namespace AppBundle\Controller;

use AppBundle\Issues\IssueListener;
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
        if ($data === null) {
            throw new \Exception('Invalid JSON body!');
        }

        $event = $request->headers->get('X-Github-Event');
        $listener = $this->get('app.issue_listener');

        switch ($event) {
            case 'issue_comment':
                $responseData = [
                    'issue' => $data['issue']['number'],
                    'status_change' => $listener->handleCommentAddedEvent(
                        $data['issue']['number'],
                        $data['comment']['body']
                    ),
                ];
                break;
            case 'pull_request':
                switch ($data['action']) {
                    case 'opened':
                        $responseData = [
                            'pull_request' => $data['pull_request']['number'],
                            'status_change' => $listener->handlePullRequestCreatedEvent(
                                $data['pull_request']['number']
                            ),
                        ];
                        break;
                    default:
                        $responseData = [
                            'unsupported_action' => $data['action']
                        ];
                }
                break;
            case 'issues':
                switch ($data['action']) {
                    case 'labeled':
                        $responseData = [
                            'issue' => $data['issue']['number'],
                            'status_change' => $listener->handleLabelAddedEvent(
                                $data['issue']['number'],
                                $data['label']['name']
                            ),
                        ];
                        break;
                    default:
                        $responseData = [
                            'unsupported_action' => $data['action']
                        ];
                }
                break;
            default:
                $responseData = [
                    'unsupported_event' => $event
                ];
        }

        return new JsonResponse($responseData);

        // 1 read in what event they have
        // 2 perform some action
        // 3 return JSON

        // log something to the database?
    }
}
