<?php

namespace App\Tests\Controller;

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
        $client->request('POST', '/webhooks/github', [], [], ['HTTP_X-Github-Event' => $eventHeader], $body);
        $response = $client->getResponse();

        $responseData = json_decode($response->getContent(), true);
        $this->assertSame(200, $response->getStatusCode(), isset($responseData['error']) ? $responseData['error'] : 'An error occurred.');

        // a weak sanity check that we went down "the right path" in the controller
        $this->assertSame($expectedResponse, $responseData);
    }

    public function getTests()
    {
        return [
            'On issue commented' => [
                'issue_comment',
                'issue_comment.created.json',
                ['issue' => 1, 'status_change' => 'needs_review'],
            ],
            'On pull request opened' => [
                'pull_request',
                'pull_request.opened.json',
                ['pull_request' => 3, 'status_change' => 'needs_review', 'pr_labels' => ['Bug']],
            ],
            'On issue labeled "bug"' => [
                'issues',
                'issues.labeled.bug.json',
                ['issue' => 5, 'status_change' => 'needs_review'],
            ],
            'On issue labeled "feature"' => [
                'issues',
                'issues.labeled.feature.json',
                ['issue' => 5, 'status_change' => null],
            ],
        ];
    }
}
