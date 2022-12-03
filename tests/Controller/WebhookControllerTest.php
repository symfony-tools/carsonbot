<?php

namespace App\Tests\Controller;

use App\Api\PullRequest\PullRequestApi;
use App\Api\Status\StatusApi;
use App\Service\RepositoryProvider;
use Happyr\ServiceMocking\ServiceMock;
use Happyr\ServiceMocking\Test\RestoreServiceContainer;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class WebhookControllerTest extends WebTestCase
{
    use RestoreServiceContainer;
    private $client;

    protected function setup(): void
    {
        if ($this->client) {
            return;
        }

        $this->client = $this->createClient();
        $repository = self::$container->get(RepositoryProvider::class);
        $statusApi = self::$container->get(StatusApi::class);
        $pullRequestApi = self::$container->get(PullRequestApi::class);

        ServiceMock::all($pullRequestApi, 'show', function ($repository, $id) {
            if (4711 !== $id) {
                return [];
            }

            return ['title' => 'Readme update', 'labels' => [
                ['name' => 'Messenger', 'color' => 'dddddd'],
            ]];
        });

        // the labels need to be off this issue for one test to pass
        $statusApi->setIssueStatus(2, null, $repository->getRepository('carsonbot-playground/symfony'));
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
        $this->assertResponseIsSuccessful(isset($responseData['error']) ? $responseData['error'] : 'An error occurred.');

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
                ['pull_request' => 3, 'status_change' => 'needs_review', 'pr_labels' => ['Console', 'Bug'], 'unsupported_branch' => '2.5', 'approved_run' => true],
            ],
            'On draft pull request opened' => [
                'pull_request',
                'pull_request.opened_draft.json',
                ['pull_request' => 3, 'draft_comment' => true, 'approved_run' => true],
            ],
            'On pull request draft to ready' => [
                'pull_request',
                'pull_request.draft_to_ready.json',
                ['pull_request' => 3, 'status_change' => 'needs_review', 'pr_labels' => ['Console', 'Bug']],
            ],
            'On pull request labeled' => [
                'pull_request',
                'pull_request.labeled.json',
                ['pull_request' => 4711, 'new_title' => '[Messenger] Readme update'],
            ],
            'On pull request opened with target branch' => [
                'pull_request',
                'pull_request.opened_target_branch.json',
                ['pull_request' => 3, 'status_change' => 'needs_review', 'pr_labels' => ['Bug'], 'milestone' => '4.4', 'unsupported_branch' => '4.4', 'approved_run' => true],
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
                ['pull_request' => 4, 'status_change' => 'needs_review',  'pr_labels' => [], 'new_contributor' => true, 'squash_comment' => true, 'approved_run' => true],
            ],
            'Waiting Code Merge' => [
                'pull_request',
                'issues.labeled.waitingCodeMerge.json',
                ['pull_request' => 2, 'milestone' => 'next'],
            ],
            'Suggest review on demand' => [
                'issue_comment',
                'pull_request.comment.json',
                ['issue' => 7, 'status_change' => null, 'suggest-review' => true],
            ],
        ];
    }
}
