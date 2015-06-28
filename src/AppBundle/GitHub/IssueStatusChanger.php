<?php

namespace AppBundle\GitHub;

use Github\Client;
use Github\Api\Issue\Labels;

class IssueStatusChanger
{
    /**
     * @var Client
     */
    private $githubClient;
    private $repositoryUsername;
    private $repositoryName;

    public function __construct(Client $githubClient, $repositoryUsername, $repositoryName)
    {
        $this->githubClient = $githubClient;
        $this->repositoryUsername = $repositoryUsername;
        $this->repositoryName = $repositoryName;
    }

    /**
     * @param integer $issueNumber The GitHub issue number
     * @param string $newStatus A StatusManager::STATUS_ constant
     */
    public function setIssueStatusLabel($issueNumber, $newStatus)
    {
        $newLabel = StatusManager::getLabelForStatus($newStatus);

        $currentLabels = $this->getIssueLabelsApi()->all(
            $this->repositoryUsername,
            $this->repositoryName,
            $issueNumber
        );

        $labelMap = StatusManager::getLabelToStatusMap();
        foreach ($currentLabels as $currentLabelData) {
            // get the name of the label
            $currentLabel = $currentLabelData['name'];

            // is the label a "status label"? No? Then skip it
            if (!isset($labelMap[$currentLabel])) {
                continue;
            }

            // if the label is already the new label, we don't need to do anything!
            if ($currentLabel == $newLabel) {
                return;
            }

            // remove the old status label
            $this->getIssueLabelsApi()->remove(
                $this->repositoryUsername,
                $this->repositoryName,
                $issueNumber,
                $currentLabel
            );
        }

        // add the new label
        $this->getIssueLabelsApi()->add(
            $this->repositoryUsername,
            $this->repositoryName,
            $issueNumber,
            $newLabel
        );
    }

    /**
     * @return Labels
     */
    private function getIssueLabelsApi()
    {
        return $this->githubClient->api('issue')->labels();
    }
}