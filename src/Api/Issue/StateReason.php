<?php

declare(strict_types=1);

namespace App\Api\Issue;

final class StateReason
{
    public const string COMPLETED = 'completed';
    public const string NOT_PLANNED = 'not_planned';
    public const string REOPENED = 'reopened';
}
