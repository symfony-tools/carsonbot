<?php

namespace AppBundle\Controller;

use AppBundle\GitHub\StatusManager;
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

        switch ($event) {
            case 'issue_comment':
                $responseData = $this->handleIssueCommentEvent($data);
                break;
            case 'pull_request':
                switch ($data['action']) {
                    case 'opened':
                        $responseData = $this->handlePullRequestCreateEvent($data);
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

    private function handleIssueCommentEvent(array $data)
    {
        $commentText = $data['comment']['body'];
        $issueNumber = $data['issue']['number'];

        $newStatus = $this->get('app.github.status_manager')
            ->getStatusChangeFromComment($commentText);

        // hacky way to not actually try to talk to GitHub when testing
        if ($this->container->getParameter('kernel.environment') != 'test') {
            $this->get('app.issue_status_changer')
                ->setIssueStatusLabel($issueNumber, $newStatus);
        }

        return [
            'issue' => $issueNumber,
            'status_change' => $newStatus,
        ];
    }

    private function handlePullRequestCreateEvent(array $data)
    {
        $prNumber = $data['pull_request']['number'];
        $newStatus = StatusManager::STATUS_NEEDS_REVIEW;

        // hacky way to not actually try to talk to GitHub when testing
        if ($this->container->getParameter('kernel.environment') != 'test') {
            $this->get('app.issue_status_changer')
                ->setIssueStatusLabel($prNumber, $newStatus);
        }

        return [
            'pull_request' => $prNumber,
            'status_change' => $newStatus,
        ];
    }
}
