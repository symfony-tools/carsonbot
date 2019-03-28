<?php

namespace AppBundle\Tests\Subscriber;

use AppBundle\Event\GitHubEvent;
use AppBundle\GitHubEvents;
use AppBundle\Repository\Repository;
use AppBundle\Subscriber\WriteIssueOnDocumentationSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcher;

class WriteIssueOnDocumentationSubscriberTest extends \PHPUnit_Framework_TestCase
{
    private $subscriber;

    private $labelsApi;

    private $issueApi;

    private $repository;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;


    protected function setUp()
    {
        $this->labelsApi = $this->getMockBuilder('AppBundle\Issues\GitHub\CachedLabelsApi')
            ->disableOriginalConstructor()
            ->getMock();

        $this->issueApi = $this->getMockBuilder('Github\Api\Issue')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->subscriber = new WriteIssueOnDocumentationSubscriber($this->labelsApi, $this->issueApi, 'symfony/symfony-docs');
        $this->repository = new Repository('weaverryan', 'symfony', [], null);

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber($this->subscriber);
    }

    public function testOnClose()
    {
        $event = new GitHubEvent([
            'action' => 'closed',
            'pull_request' => $this->getPullRequestData(array(
                'merged' => false
            )),
        ], $this->repository);

        $this->dispatcher->dispatch(GitHubEvents::PULL_REQUEST, $event);

        $responseData = $event->getResponseData();

        $this->assertCount(1, $responseData);
        $this->assertSame('closed', $responseData['unsupported_action']);
    }

    public function testOnMergeNonFeature()
    {
        $this->labelsApi->expects($this->once())
            ->method('getIssueLabels')
            ->with(1234, $this->repository)
            ->willReturn(array('Bug', 'Finder', 'Status: Needs Review'));

        $event = new GitHubEvent([
            'action' => 'closed',
            'pull_request' => $this->getPullRequestData(),
        ], $this->repository);

        $this->dispatcher->dispatch(GitHubEvents::PULL_REQUEST, $event);

        $responseData = $event->getResponseData();

        $this->assertCount(1, $responseData);
        $this->assertSame('PR does not have "Feature" label.', $responseData['unsupported_reason']);
    }

    public function testOnMergeFeatureWithDocs()
    {
        $this->labelsApi->expects($this->once())
            ->method('getIssueLabels')
            ->with(1234, $this->repository)
            ->willReturn(array('Finder', 'Feature', 'Status: Reviewed'));

        $event = new GitHubEvent([
            'action' => 'closed',
            'pull_request' => $this->getPullRequestData(array(
                'body' => 'I have written docs at https://github.com/symfony/symfony-docs. You should check it out',
            )),
        ], $this->repository);

        $this->dispatcher->dispatch(GitHubEvents::PULL_REQUEST, $event);

        $responseData = $event->getResponseData();

        $this->assertCount(1, $responseData);
        $this->assertSame('PR have a reference to symfony/symfony-docs', $responseData['unsupported_reason']);
    }

    public function testOnMergeFeatureWithoutDocs()
    {
        $this->labelsApi->expects($this->once())
            ->method('getIssueLabels')
            ->with(1234, $this->repository)
            ->willReturn(array('Finder', 'Feature', 'Status: Reviewed'));

        $issueUrl = 'https://github.com/symfony/symfony-docs/issues/4711';
        $this->issueApi->expects($this->once())
            ->method('create')
            ->with('symfony', 'symfony-docs', $this->callback(
                function($argument){
                    return is_array($argument) && isset($argument['title']) && isset($argument['body']);
                }
            ))
            ->willReturn(array('html_url'=> $issueUrl));

        $event = new GitHubEvent([
            'action' => 'closed',
            'pull_request' => $this->getPullRequestData(array(
                'body' => 'I have forgotten the docs',
            )),
        ], $this->repository);

        $this->dispatcher->dispatch(GitHubEvents::PULL_REQUEST, $event);

        $responseData = $event->getResponseData();

        $this->assertCount(2, $responseData);
        $this->assertSame(1234, $responseData['pull_request']);
        $this->assertSame('Created issue on docs: '.$issueUrl, $responseData['action']);
    }


    private function getPullRequestData(array $data = array())
    {
        return array_merge(array(
            'number' => 1234,
            'title' => 'Example title',
            'url' => 'https://api.github.com/repos/weaverryan/symfony/pulls/3',
            'html_url' => 'https://github.com/weaverryan/symfony/pull/3',
            'merged' => true,
            'user'=> array('login' => 'Nyholm'),
            'body' => 'PR body',
        ), $data);
    }
}
