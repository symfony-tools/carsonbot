<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SymfonyVersionProvider
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
    ) {
    }

    public function getCurrentVersion(): string
    {
        $httpClient = $this->httpClient;

        return $this->cache->get('symfony_version', function (ItemInterface $item) use ($httpClient) {
            $defaultValue = '99.99';
            try {
                $response = $httpClient->request('GET', 'https://symfony.com/releases.json');
                $data = $response->toArray(true);
                $version = $data['latest_stable_version'] ?? $defaultValue;
            } catch (\Throwable) {
                $version = $defaultValue;
            }

            $item->expiresAfter($version === $defaultValue ? 300 : 86400);

            return $version;
        });
    }

    /**
     * @return array<int, string>
     *
     * @throws \RuntimeException
     */
    public function getMaintainedVersions(): array
    {
        $response = $this->httpClient->request('GET', 'https://symfony.com/releases.json');
        $data = $response->toArray(true);
        $versions = $data['maintained_versions'] ?? null;

        if (is_array($versions) && count($versions) > 0) {
            return $versions;
        }

        throw new \RuntimeException('Could not fetch maintained versions');
    }
}
