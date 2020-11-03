<?php

declare(strict_types=1);

namespace App\Sercice;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SymfonyVersionProvider
{
    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var CacheInterface
     */
    private $cache;

    public function __construct(HttpClientInterface $httpClient, CacheInterface $cache)
    {
        $this->httpClient = $httpClient;
        $this->cache = $cache;
    }

    public function getCurrentVersion()
    {
        /** @var $httpClient */
        $httpClient = $this->httpClient;

        return $this->cache->get('symfony_version', function (ItemInterface $item) use ($httpClient) {
            $defaultValue = '99.99';
            try {
                $response = $httpClient->request('GET', 'https://symfony.com/releases.json');
                $data = $response->toArray(true);
                $version = $data['latest_stable_version'] ?? $defaultValue;
            } catch (\Throwable $e) {
                $version = $defaultValue;
            }

            $item->expiresAfter($version === $defaultValue ? 300 : 604800);

            return $version;
        });
    }
}
