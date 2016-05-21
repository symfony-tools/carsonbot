<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class WebhookControllerTest extends WebTestCase
{
    /**
     * @dataProvider getTests
     */
    public function testIssueComment($eventHeader, $payloadFilename, $expectedResponse)
    {
        $client = $this->createClient();
        $body = file_get_contents(__DIR__.'/../webhook_examples/'.$payloadFilename);
        $client->request('POST', '/webhooks/github', array(), array(), array('HTTP_X-Github-Event' => $eventHeader), $body);
        $response = $client->getResponse();

        $responseData = json_decode($response->getContent(), true);
        $this->assertSame(200, $response->getStatusCode(), isset($responseData['error']) ? $responseData['error'] : 'An error occurred.');

        // a weak sanity check that we went down "the right path" in the controller
        $this->assertSame($expectedResponse, $responseData);
    }

    public function getTests()
    {
        return array(
            'On issue commented' => array(
                'issue_comment',
                'issue_comment.created.json',
                array('issue' => 1, 'status_change' => 'needs_review'),
            ),
            'On pull request opened' => array(
                'pull_request',
                'pull_request.opened.json',
                array('pull_request' => 3, 'status_change' => 'needs_review', 'pr_labels' => array('Bug')),
            ),
            'On issue labeled "bug"' => array(
                'issues',
                'issues.labeled.bug.json',
                array('issue' => 5, 'status_change' => 'needs_review'),
            ),
            'On issue labeled "feature"' => array(
                'issues',
                'issues.labeled.feature.json',
                array('issue' => 5, 'status_change' => null),
            ),
        );
    }
}
