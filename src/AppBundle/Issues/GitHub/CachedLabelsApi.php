<?php

namespace AppBundle\Issues\GitHub;

use AppBundle\Repository\Repository;
use Github\Api\Issue\Labels;

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

    public function __construct(Labels $labelsApi)
    {
        $this->labelsApi = $labelsApi;
    }

    public function getIssueLabels($issueNumber, Repository $repository)
    {
        $key = $this->getCacheKey($issueNumber, $repository);
        if (!isset($this->labelCache[$key])) {
            $this->labelCache[$key] = [];

            $labelsData = $this->labelsApi->all(
                $repository->getVendor(),
                $repository->getName(),
                $issueNumber
            );

            // Load labels, keep only the first status label
            foreach ($labelsData as $labelData) {
                $this->labelCache[$key][$labelData['name']] = true;
            }
        }

        return array_keys($this->labelCache[$key]);
    }

    public function addIssueLabel($issueNumber, $label, Repository $repository)
    {
        $key = $this->getCacheKey($issueNumber, $repository);

        if (isset($this->labelCache[$key][$label])) {
            return;
        }

        $this->labelsApi->add(
            $repository->getVendor(),
            $repository->getName(),
            $issueNumber,
            $label
        );

        // Update cache if already loaded
        if (isset($this->labelCache[$key])) {
            $this->labelCache[$key][$label] = true;
        }
    }

    public function removeIssueLabel($issueNumber, $label, Repository $repository)
    {
        $key = $this->getCacheKey($issueNumber, $repository);
        if (isset($this->labelCache[$key]) && !isset($this->labelCache[$key][$label])) {
            return;
        }

        $this->labelsApi->remove(
            $repository->getVendor(),
            $repository->getName(),
            $issueNumber,
            $label
        );

        // Update cache if already loaded
        if (isset($this->labelCache[$key])) {
            unset($this->labelCache[$key][$label]);
        }
    }

    public function addIssueLabels($issueNumber, array $labels, Repository $repository)
    {
        foreach ($labels as $label) {
            $this->addIssueLabel($issueNumber, $label, $repository);
        }
    }

    private function getCacheKey($issueNumber, Repository $repository)
    {
        return sprintf('%s_%s_%s', $issueNumber, $repository->getVendor(), $repository->getName());
    }
}
