<?php

declare(strict_types=1);

namespace App\Api\Label;

use App\Model\Repository;

class NullLabelApi implements LabelApi
{
    public function getIssueLabels($issueNumber, Repository $repository): array
    {
        return [];
    }

    public function addIssueLabel($issueNumber, $label, Repository $repository)
    {
    }

    public function removeIssueLabel($issueNumber, $label, Repository $repository)
    {
    }

    public function addIssueLabels($issueNumber, array $labels, Repository $repository)
    {
    }

    public function getAllLabelsForRepository(Repository $repository): array
    {
        return [];
    }

    public function getComponentLabelsForRepository(Repository $repository): array
    {
        return [];
    }
}
