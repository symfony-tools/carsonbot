<?php

namespace App\Api\Label;

use App\Model\Repository;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
interface LabelApi
{
    /**
     * @return string[]
     */
    public function getIssueLabels(int $issueNumber, Repository $repository): array;

    public function addIssueLabel(int $issueNumber, string $label, Repository $repository): void;

    public function removeIssueLabel(int $issueNumber, string $label, Repository $repository): void;

    /**
     * @param string[] $labels
     */
    public function addIssueLabels(int $issueNumber, array $labels, Repository $repository): void;

    /**
     * @return string[]
     */
    public function getAllLabelsForRepository(Repository $repository): array;
}
