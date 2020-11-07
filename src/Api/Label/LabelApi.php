<?php

namespace App\Api\Label;

use App\Model\Repository;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
interface LabelApi
{
    public function getIssueLabels($issueNumber, Repository $repository): array;

    public function addIssueLabel($issueNumber, string $label, Repository $repository);

    public function removeIssueLabel($issueNumber, string $label, Repository $repository);

    public function addIssueLabels($issueNumber, array $labels, Repository $repository);

    /**
     * @return string[]
     */
    public function getAllLabelsForRepository(Repository $repository): array;

    /**
     * @return string[]
     */
    public function getComponentLabelsForRepository(Repository $repository): array;
}
