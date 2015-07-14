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
     * @param string  $newStatus A StatusManager::STATUS_ constant
     * @param bool    $replaceExistingStatus If true, the status WILL be set. If false, it's only
     *                                       set if there isn't already a "status" label
     */
    public function setIssueStatusLabel($issueNumber, $newStatus, $replaceExistingStatus = true)
    {
        $newLabel = StatusManager::getLabelForStatus($newStatus);

        $currentLabels = $this->getIssueLabelsApi()->all(
            $this->repositoryUsername,
            $this->repositoryName,
            $issueNumber
        );

        $labelMap = StatusManager::getLabelToStatusMap();
        $addLabel = true;
        foreach ($currentLabels as $currentLabelData) {
            // get the name of the label
            $currentLabel = $currentLabelData['name'];

            // is the label a "status label"? No? Then skip it
            if (!isset($labelMap[$currentLabel])) {
                continue;
            }

            // if the label is already the new label, we don't need to do anything!
            if ($currentLabel == $newLabel) {
                // we will *not* need to add the label later on
                $addLabel = false;
                continue;
            }

            if (!$replaceExistingStatus) {
                // there IS an existing status, but we should not replace it
                // in fact, we should do nothing - leave the current status

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

        if ($addLabel) {
            // add the new label
            $this->getIssueLabelsApi()->add(
                $this->repositoryUsername,
                $this->repositoryName,
                $issueNumber,
                $newLabel
            );
        }
    }

    /**
     * @return Labels
     */
    private function getIssueLabelsApi()
    {
        return $this->githubClient->api('issue')->labels();
    }
}