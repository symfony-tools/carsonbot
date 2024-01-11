<?php

namespace App\Model;

use App\Api\Status\GitHubStatusApi;

/**
 * @author Ener-Getick <egetick@gmail.com>
 */
class Repository
{
    /**
     * @var string
     */
    private $vendor;

    /**
     * @var string
     */
    private $name;

    /**
     * The webhook secret used by GitHub.
     *
     * @var string|null
     */
    private $secret;

    public function __construct(string $vendor, string $name, string $secret = null)
    {
        $this->vendor = $vendor;
        $this->name = $name;
        $this->secret = $secret;
    }

    public function getVendor(): string
    {
        return $this->vendor;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSecret(): ?string
    {
        return $this->secret;
    }

    public function getNeedsReviewUrl(): string
    {
        return sprintf(
            'https://github.com/%s/%s/labels/%s',
            $this->getVendor(),
            $this->getName(),
            rawurlencode(GitHubStatusApi::getNeedsReviewLabel())
        );
    }

    public function getFullName(): string
    {
        return sprintf('%s/%s', $this->getVendor(), $this->getName());
    }
}
