<?php

namespace App\Model;

use App\Api\Status\GitHubStatusApi;

/**
 * @author Ener-Getick <egetick@gmail.com>
 */
class Repository
{
    /**
     * @param string|null   $secret        the webhook secret used by GitHub
     * @param list<string>  $ignoredLabels labels that should not be auto-applied from PR/issue titles
     */
    public function __construct(
        private readonly string $vendor,
        private readonly string $name,
        private readonly ?string $secret = null,
        private readonly array $ignoredLabels = [],
    ) {
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

    /**
     * @return list<string>
     */
    public function getIgnoredLabels(): array
    {
        return $this->ignoredLabels;
    }

    public function getFullName(): string
    {
        return sprintf('%s/%s', $this->getVendor(), $this->getName());
    }
}
