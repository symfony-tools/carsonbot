<?php

namespace App\Api\Label;

use App\Model\Repository;
use Github\Api\Issue\Labels;
use Github\Exception\RuntimeException;
use Github\ResultPager;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class GithubLabelApi implements LabelApi
{
    /**
     * In memory cache for specific issues.
     *
     * @var array<string, array<string, bool>>
     */
    private array $labelCache = [];

    public function __construct(
        private readonly Labels $labelsApi,
        private readonly ResultPager $resultPager,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getIssueLabels(int $issueNumber, Repository $repository): array
    {
        $key = $this->getCacheKey($issueNumber, $repository);
        if (!isset($this->labelCache[$key])) {
            $this->labelCache[$key] = [];

            $labelsData = $this->labelsApi->all(
                $repository->getVendor(),
                $repository->getName(),
                $issueNumber
            );

            // Load labels, keep only the first status label
            foreach ($labelsData as $labelData) {
                $this->labelCache[$key][$labelData['name']] = true;
            }
        }

        $labels = array_keys($this->labelCache[$key]);
        $this->logger->debug('Returning labels for {repo}#{issue}', ['repo' => $repository->getFullName(), 'issue' => $issueNumber]);

        return $labels;
    }

    public function addIssueLabel(int $issueNumber, string $label, Repository $repository): void
    {
        $this->addIssueLabels($issueNumber, [$label], $repository);
    }

    public function removeIssueLabel(int $issueNumber, string $label, Repository $repository): void
    {
        $key = $this->getCacheKey($issueNumber, $repository);
        if (isset($this->labelCache[$key]) && !isset($this->labelCache[$key][$label])) {
            return;
        }

        try {
            $this->labelsApi->remove($repository->getVendor(), $repository->getName(), $issueNumber, $label);
        } catch (RuntimeException $e) {
            // We can just ignore 404 exceptions.
            if (404 !== $e->getCode()) {
                throw $e;
            }
        }

        // Update cache if already loaded
        if (isset($this->labelCache[$key])) {
            unset($this->labelCache[$key][$label]);
        }
    }

    public function addIssueLabels(int $issueNumber, array $labels, Repository $repository): void
    {
        $key = $this->getCacheKey($issueNumber, $repository);
        $labelsToAdd = [];

        foreach ($labels as $label) {
            if (!isset($this->labelCache[$key][$label])) {
                $labelsToAdd[] = $label;
            }
        }

        if ([] !== $labelsToAdd) {
            $this->labelsApi->add($repository->getVendor(), $repository->getName(), $issueNumber, $labelsToAdd);
        }

        // Update cache if already loaded
        foreach ($labels as $label) {
            if (isset($this->labelCache[$key])) {
                $this->labelCache[$key][$label] = true;
            }
        }
    }

    /**
     * @return string[]
     */
    public function getAllLabelsForRepository(Repository $repository): array
    {
        $allLabels = $this->getAllLabels($repository);

        return array_column($allLabels, 'name');
    }

    /**
     * @return array<array{name: string, color: string}>
     */
    private function getAllLabels(Repository $repository): array
    {
        $key = 'labels_'.sha1($repository->getFullName());

        return $this->cache->get($key, function (ItemInterface $item) use ($repository): array {
            $labels = $this->resultPager->fetchAll($this->labelsApi, 'all', [$repository->getVendor(), $repository->getName()]);
            $item->expiresAfter(604800);

            return $labels;
        });
    }

    private function getCacheKey(int $issueNumber, Repository $repository): string
    {
        return sprintf('%s_%s_%s', $issueNumber, $repository->getVendor(), $repository->getName());
    }
}
