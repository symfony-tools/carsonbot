<?php

namespace App\Event;

use App\Model\Repository;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @author Jules Pietri <jules@heahprod.com>
 */
class GitHubEvent extends Event
{
    /** @var array<string, mixed> */
    protected array $responseData = [];

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly array $data,
        private readonly Repository $repository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function getRepository(): Repository
    {
        return $this->repository;
    }

    /**
     * @return array<string, mixed>
     */
    public function getResponseData(): array
    {
        return $this->responseData;
    }

    /**
     * @param array<string, mixed> $responseData
     */
    public function setResponseData(array $responseData): void
    {
        foreach ($responseData as $k => $v) {
            $this->responseData[$k] = $v;
        }
    }
}
