<?php

namespace AppBundle\Issues\GitHub;

use Github\Api\Issue\Labels;
use AppBundle\Repository\RepositoryStack;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class CachedLabelsApi
{
    /**
     * @var Labels
     */
    private $labelsApi;

    /**
     * @var string[][]
     */
    private $labelCache = [];

    /**
     * @var RepositoryStack
     */
    private $repositoryStack;

    public function __construct(Labels $labelsApi, RepositoryStack $repositoryStack)
    {
        $this->labelsApi = $labelsApi;
        $this->repositoryStack = $repositoryStack;
    }

    public function getIssueLabels($issueNumber)
    {
        if (!isset($this->labelCache[$issueNumber])) {
            $this->labelCache[$issueNumber] = [];

            $repository = $this->repositoryStack->getCurrentRepository();
            $labelsData = $this->labelsApi->all(
                $repository->getVendor(),
                $repository->getName(),
                $issueNumber
            );

            // Load labels, keep only the first status label
            foreach ($labelsData as $labelData) {
                $this->labelCache[$issueNumber][$labelData['name']] = true;
            }
        }

        return array_keys($this->labelCache[$issueNumber]);
    }

    public function addIssueLabel($issueNumber, $label)
    {
        if (isset($this->labelCache[$issueNumber][$label])) {
            return;
        }

        $repository = $this->repositoryStack->getCurrentRepository();
        $this->labelsApi->add(
            $repository->getVendor(),
            $repository->getName(),
            $issueNumber,
            $label
        );

        // Update cache if already loaded
        if (isset($this->labelCache[$issueNumber])) {
            $this->labelCache[$issueNumber][$label] = true;
        }
    }

    public function removeIssueLabel($issueNumber, $label)
    {
        if (isset($this->labelCache[$issueNumber]) && !isset($this->labelCache[$issueNumber][$label])) {
            return;
        }

        $repository = $this->repositoryStack->getCurrentRepository();
        $this->labelsApi->remove(
            $repository->getVendor(),
            $repository->getName(),
            $issueNumber,
            $label
        );

        // Update cache if already loaded
        if (isset($this->labelCache[$issueNumber])) {
            unset($this->labelCache[$issueNumber][$label]);
        }
    }
}
