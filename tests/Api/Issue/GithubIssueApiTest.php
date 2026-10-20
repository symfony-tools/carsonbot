<?php

namespace App\Tests\Api\Issue;

use App\Api\Issue\GithubIssueApi;
use App\Model\Repository;
use Github\Api\Issue;
use Github\Api\Issue\Comments;
use Github\Api\Issue\Timeline;
use Github\Api\Search;
use Github\ResultPager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
class GithubIssueApiTest extends TestCase
{
    public const USER_NAME = 'weaverryan';

    public const REPO_NAME = 'carson';

    public const BOT_USERNAME = 'carsonbot';

    private GithubIssueApi $api;

    private ResultPager|MockObject $resultPager;

    private Comments|MockObject $issueCommentApi;

    private Issue|MockObject $backendApi;

    private Search|MockObject $searchApi;

    private Timeline|MockObject $timelineApi;

    private Repository $repository;

    protected function setUp(): void
    {
        $this->backendApi = $this->getMockBuilder(Issue::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultPager = $this->getMockBuilder(ResultPager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->issueCommentApi = $this->getMockBuilder(Comments::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchApi = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->timelineApi = $this->getMockBuilder(Timeline::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->backendApi->expects($this->any())
            ->method('timeline')
            ->willReturn($this->timelineApi);

        $this->api = new GithubIssueApi(
            $this->resultPager,
            $this->issueCommentApi,
            $this->backendApi,
            $this->searchApi,
            self::BOT_USERNAME
        );

        $this->repository = new Repository(
            self::USER_NAME,
            self::REPO_NAME,
            null
        );
    }

    public function testClose()
    {
        $this->backendApi->expects($this->once())
            ->method('update')
            ->with(
                self::USER_NAME,
                self::REPO_NAME,
                1234,
                [
                    'state' => 'closed',
                    'state_reason' => 'not_planned',
                ]
            );

        $this->api->close($this->repository, 1234);
    }

    public function testCommentOnIssue()
    {
        $commentBody = 'This is a test comment';

        $this->issueCommentApi->expects($this->once())
            ->method('create')
            ->with(
                self::USER_NAME,
                self::REPO_NAME,
                1234,
                ['body' => $commentBody]
            );

        $this->api->commentOnIssue($this->repository, 1234, $commentBody);
    }

    public function testShow()
    {
        $issueData = ['title' => 'Test Issue', 'body' => 'Issue description'];

        $this->backendApi->expects($this->once())
            ->method('show')
            ->with(self::USER_NAME, self::REPO_NAME, 1234)
            ->willReturn($issueData);

        $this->assertSame($issueData, $this->api->show($this->repository, 1234));
    }

    public function testLastCommentWasMadeByBot()
    {
        $comments = [
            ['user' => ['login' => 'user1']],
            ['user' => ['login' => self::BOT_USERNAME]],
        ];

        $this->issueCommentApi->expects($this->once())
            ->method('all')
            ->with(self::USER_NAME, self::REPO_NAME, 1234, ['per_page' => 100])
            ->willReturn($comments);

        $this->assertTrue($this->api->lastCommentWasMadeByBot($this->repository, 1234));
    }

    public function testLastCommentWasNotMadeByBot()
    {
        $comments = [
            ['user' => ['login' => self::BOT_USERNAME]],
            ['user' => ['login' => 'user1']],
        ];

        $this->issueCommentApi->expects($this->once())
            ->method('all')
            ->with(self::USER_NAME, self::REPO_NAME, 1234, ['per_page' => 100])
            ->willReturn($comments);

        $this->assertFalse($this->api->lastCommentWasMadeByBot($this->repository, 1234));
    }

    public function testOpenCreatesNewIssue()
    {
        $title = 'Test Issue';
        $body = 'Issue description';
        $labels = ['bug', 'help wanted'];

        $this->resultPager->expects($this->once())
            ->method('fetchAllLazy')
            ->with($this->searchApi, 'issues', [
                sprintf('repo:%s/%s "%s" is:open author:%s', self::USER_NAME, self::REPO_NAME, $title, self::BOT_USERNAME),
                'updated',
                'desc',
            ])
            ->willReturn((fn () => yield from [])());

        $this->backendApi->expects($this->once())
            ->method('create')
            ->with(
                self::USER_NAME,
                self::REPO_NAME,
                [
                    'title' => $title,
                    'labels' => $labels,
                    'body' => $body,
                ]
            );

        $this->api->open($this->repository, $title, $body, $labels);
    }

    public function testOpenUpdatesExistingIssue()
    {
        $title = 'Test Issue';
        $body = 'Updated description';
        $labels = ['bug', 'help wanted'];
        $issueNumber = 1234;

        $this->resultPager->expects($this->once())
            ->method('fetchAllLazy')
            ->willReturn((fn () => yield ['number' => 1234])());

        $this->backendApi->expects($this->once())
            ->method('update')
            ->with(
                self::USER_NAME,
                self::REPO_NAME,
                $issueNumber,
                [
                    'title' => $title,
                    'body' => $body,
                ]
            );

        $this->api->open($this->repository, $title, $body, $labels);
    }

    #[DataProvider('provideTimelines')]
    public function testHasOpenPullRequest(array $events, bool $expected)
    {
        $this->resultPager->expects($this->once())
            ->method('fetchAllLazy')
            ->with($this->timelineApi, 'all', [self::USER_NAME, self::REPO_NAME, 1234])
            ->willReturn((fn () => yield from $events)());

        $this->assertSame($expected, $this->api->hasOpenPullRequest($this->repository, 1234));
    }

    public static function provideTimelines(): iterable
    {
        $pullRequest = static fn (string $state, string $repository = self::USER_NAME.'/'.self::REPO_NAME): array => [
            'event' => 'cross-referenced',
            'source' => ['type' => 'issue', 'issue' => [
                'state' => $state,
                'pull_request' => [],
                'repository' => ['full_name' => $repository],
            ]],
        ];

        yield 'no events' => [[], false];
        yield 'open pull request' => [[['event' => 'commented'], $pullRequest('open')], true];
        yield 'closed pull request' => [[$pullRequest('closed')], false];
        yield 'pull request of another repository' => [[$pullRequest('open', 'other/repo')], false];
        yield 'issue' => [[[
            'event' => 'cross-referenced',
            'source' => ['type' => 'issue', 'issue' => [
                'state' => 'open',
                'repository' => ['full_name' => self::USER_NAME.'/'.self::REPO_NAME],
            ]],
        ]], false];
    }

    public function testFindStaleIssues()
    {
        $date = new \DateTimeImmutable('2022-01-01');
        $issues = [
            ['number' => 1234, 'title' => 'Stale issue 1'],
            ['number' => 5678, 'title' => 'Stale issue 2'],
        ];
        $openPullRequest = [
            'event' => 'cross-referenced',
            'source' => ['type' => 'issue', 'issue' => [
                'state' => 'open',
                'pull_request' => [],
                'repository' => ['full_name' => self::USER_NAME.'/'.self::REPO_NAME],
            ]],
        ];

        $this->resultPager->expects($this->exactly(3))
            ->method('fetchAllLazy')
            ->willReturnCallback(function ($api, string $method, array $parameters) use ($issues, $openPullRequest) {
                if ($this->searchApi === $api) {
                    $this->assertSame('issues', $method);
                    $this->assertSame([
                        sprintf('repo:%s/%s is:issue -label:"Keep open" -label:"Missing translations" is:open -linked:pr updated:<2022-01-01', self::USER_NAME, self::REPO_NAME),
                        'updated',
                        'desc',
                    ], $parameters);

                    yield from $issues;

                    return;
                }

                $this->assertSame($this->timelineApi, $api);

                // the second issue has a pull request targeting a maintenance branch
                if (5678 === $parameters[2]) {
                    yield $openPullRequest;
                }
            });

        $this->assertSame([$issues[0]], iterator_to_array($this->api->findStaleIssues($this->repository, $date), false));
    }
}
