<?php

namespace App\Event;

use App\Model\Repository;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @author Jules Pietri <jules@heahprod.com>
 */
class GitHubEvent extends Event
{
    protected array $responseData = [];

    public function __construct(
        private readonly array $data,
        private readonly Repository $repository,
    ) {
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getRepository(): Repository
    {
        return $this->repository;
    }

    public function getResponseData(): array
    {
        return $this->responseData;
    }

    public function setResponseData(array $responseData): void
    {
        foreach ($responseData as $k => $v) {
            $this->responseData[$k] = $v;
        }
    }
}
