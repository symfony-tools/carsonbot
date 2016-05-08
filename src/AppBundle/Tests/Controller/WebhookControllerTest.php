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
        $this->assertEquals(200, $response->getStatusCode());

        // a weak sanity check that we went down "the right path" in the controller
        $this->assertEquals($expectedResponse, $responseData);
    }

    public function testInvalidSignature()
    {
        $client = $this->createClient();
        $body = file_get_contents(__DIR__.'/../webhook_examples/secured_hook.json');
        $client->request('POST', '/webhooks/github', array(), array(), array('HTTP_X_HUB_SIGNATURE' => 'foo'), $body);

        $response = $client->getResponse();
        $this->assertContains('Secret does not match', $response->getContent());
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @requires extension hash
     */
    public function testValidSignature()
    {
        $client = $this->createClient();
        $body = file_get_contents(__DIR__.'/../webhook_examples/secured_hook.json');
        $client->request('POST', '/webhooks/github', array(), array(), array('HTTP_X-Github-Event' => 'foo', 'HTTP_X_HUB_SIGNATURE' => 'sha1='.hash_hmac('sha1', $body, 'mysecretisnotsecret')), $body);

        $response = $client->getResponse();
        $this->assertEquals(['unsupported_event' => 'foo'], json_decode($response->getContent(), true));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testUnknowRepository()
    {
        $client = $this->createClient();
        $body = file_get_contents(__DIR__.'/../webhook_examples/issues.labeled.unknown_repository.json');
        $client->request('POST', '/webhooks/github', array(), array(), array('HTTP_X-Github-Event' => 'issues'), $body);
        $response = $client->getResponse();

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function getTests()
    {
        $tests = array();
        $tests[] = array(
            'issue_comment',
            'issue_comment.created.json',
            array('status_change' => 'needs_review', 'issue' => 1),
        );
        $tests[] = array(
            'pull_request',
            'pull_request.opened.json',
            array('status_change' => 'needs_review', 'pull_request' => 3),
        );
        $tests[] = array(
            'issues',
            'issues.labeled.bug.json',
            array('status_change' => 'needs_review', 'issue' => 5),
        );
        $tests[] = array(
            'issues',
            'issues.labeled.feature.json',
            array('status_change' => null, 'issue' => 5),
        );

        return $tests;
    }
}
