<?php

namespace App\Tests\Controller;

use App\Api\Status\StatusApi;
use App\Service\RepositoryProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class WebhookControllerTest extends WebTestCase
{
    private $client;

    public function setup()
    {
        if ($this->client) {
            return;
        }

        $this->client = $this->createClient();
        $repository = self::$container->get(RepositoryProvider::class);
        $statusApi = self::$container->get(StatusApi::class);

        // the labels need to be off this issue for one test to pass
        $statusApi->setIssueStatus(
            2,
            null,
            $repository->getRepository('carsonbot-playground/symfony')
        );
    }

    /**
     * @dataProvider getTests
     */
    public function testIssueComment($eventHeader, $payloadFilename, $expectedResponse)
    {
        $client = $this->client;
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
                ['pull_request' => 3, 'status_change' => 'needs_review', 'pr_labels' => ['Console', 'Bug']],
            ],
            'On pull request labeled' => [
                'pull_request',
                'pull_request.labeled.json',
                ['pull_request' => 3, 'new_title' => '[Messenger] Readme update'],
            ],
            'On pull request opened with target branch' => [
                'pull_request',
                'pull_request.opened_target_branch.json',
                ['pull_request' => 3, 'status_change' => 'needs_review', 'pr_labels' => ['Bug'], 'milestone' => '4.4'],
            ],
            'On issue labeled bug' => [
                'issues',
                'issues.labeled.bug.json',
                ['issue' => 2, 'status_change' => 'needs_review'],
            ],
            'On issue labeled "feature"' => [
                'issues',
                'issues.labeled.feature.json',
                ['issue' => 2, 'status_change' => null],
            ],
            'Welcome first users' => [
                'pull_request',
                'pull_request.new_contributor.json',
                ['pull_request' => 4, 'status_change' => 'needs_review',  'pr_labels' => [], 'new_contributor' => true],
            ],
        ];
    }
}
