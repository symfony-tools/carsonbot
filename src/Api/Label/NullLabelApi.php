<?php

declare(strict_types=1);

namespace App\Api\Label;

use App\Model\Repository;

class NullLabelApi implements LabelApi
{
    public function getIssueLabels(int $issueNumber, Repository $repository): array
    {
        return [];
    }

    public function addIssueLabel(int $issueNumber, string $label, Repository $repository): void
    {
    }

    public function removeIssueLabel(int $issueNumber, string $label, Repository $repository): void
    {
    }

    public function addIssueLabels(int $issueNumber, array $labels, Repository $repository): void
    {
    }

    public function getAllLabelsForRepository(Repository $repository): array
    {
        return [];
    }
}
