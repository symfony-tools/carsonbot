<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class WebhookControllerTest extends WebTestCase
{
    public function testIssueComment()
    {
        $client = $this->createClient();
        $body = file_get_contents(__DIR__.'/../webhook_examples/issue_comment.created.json');
        $client->request('POST', '/webhooks/github', array(), array(), array('HTTP_X-Github-Event' => 'issue_comment'), $body);
        $response = $client->getResponse();

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode());
        // a weak sanity check that we went down "the right path" in the controller
        $this->assertEquals($responseData['status_change'], 'needs_review');
        $this->assertEquals($responseData['issue'], 1);
    }

}