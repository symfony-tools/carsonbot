<?php

namespace AppBundle\Tests\GitHub;

use AppBundle\GitHub\IssueStatusChanger;
use AppBundle\GitHub\StatusManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class IssueStatusChangerTest extends KernelTestCase
{
    public function testIntegrationSetIssueStatusLabel()
    {
        self::bootKernel();
        $container = self::$kernel->getContainer();

        /** @var IssueStatusChanger $issueStatusChanger */
        $issueStatusChanger = $container->get('app.issue_status_changer');
        $testIssueNumber = $this->getTestIssueNumber();

        $issueStatusChanger->setIssueStatusLabel($testIssueNumber, StatusManager::STATUS_NEEDS_WORK);
        $this->assertIssueLabels(
            $testIssueNumber,
            array(StatusManager::getLabelForStatus(StatusManager::STATUS_NEEDS_WORK))
        );

        // try to set it, but don't "replace" it. So, nothing will happen
        $issueStatusChanger->setIssueStatusLabel($testIssueNumber, StatusManager::STATUS_REVIEWED, false);
        $this->assertIssueLabels(
            $testIssueNumber,
            array(StatusManager::getLabelForStatus(StatusManager::STATUS_NEEDS_WORK))
        );
    }

    /**
     * Guarantees there's an issue on GitHub we can use for integration testing
     *
     * @return integer
     */
    private function getTestIssueNumber()
    {
        $container = self::$kernel->getContainer();

        $repoUsername = $container->getParameter('repository_username');
        $repoName = $container->getParameter('repository_name');
        $githubClient = $container->get('app.github_client');
        $data = $githubClient->api('issue')->find(
            $repoUsername, $repoName, 'open', 'IntegrationTest'
        );

        if (isset($data['issues'][0])) {
            $issueNumber = $data['issues'][0]['number'];
        } else {
            $data = $githubClient->api('issue')->create(
                $repoUsername,
                $repoName,
                array(
                    'title' => 'IntegrationTest issue',
                    'body' => 'This is an issue used for integration testing'
                )
            );

            $issueNumber = $data['number'];
        }

        // clear all the labels
        $githubClient->issues()->labels()->clear(
            $repoUsername,
            $repoName,
            $issueNumber
        );
        // give it a Status: Reviewed
        $githubClient->issues()->labels()->add(
            $repoUsername,
            $repoName,
            $issueNumber,
            StatusManager::getLabelForStatus(StatusManager::STATUS_REVIEWED)
        );

        return $issueNumber;
    }

    private function assertIssueLabels($issueNumber, $expectedLabels)
    {
        $container = self::$kernel->getContainer();

        $repoUsername = $container->getParameter('repository_username');
        $repoName = $container->getParameter('repository_name');
        $githubClient = $container->get('app.github_client');
        $data = $githubClient->issues()->labels()->all(
            $repoUsername, $repoName, $issueNumber
        );

        $actualLabelNames = array_map(function($value) {
            return $value['name'];
        }, $data);

        $this->assertEquals($expectedLabels, $actualLabelNames);
    }
}
