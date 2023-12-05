<?php

namespace App\Service;

use App\Api\Label\LabelApi;
use App\Model\Repository;
use Psr\Log\LoggerInterface;

/**
 * Extract label name from a PR/Issue.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class LabelNameExtractor
{
    private $labelsApi;
    private $logger;

    private static $labelAliases = [
        'bridge\doctrine' => 'DoctrineBridge',
        'bridge/doctrine' => 'DoctrineBridge',
        'bridge\monolog' => 'MonologBridge',
        'bridge/monolog' => 'MonologBridge',
        'bridge\phpunit' => 'PhpUnitBridge',
        'bridge/phpunit' => 'PhpUnitBridge',
        'bridge\proxymanager' => 'ProxyManagerBridge',
        'bridge/proxymanager' => 'ProxyManagerBridge',
        'bridge\twig' => 'TwigBridge',
        'bridge/twig' => 'TwigBridge',
        'di' => 'DependencyInjection',
        'fwb' => 'FrameworkBundle',
        'profiler' => 'WebProfilerBundle',
        'router' => 'Routing',
        'translator' => 'Translation',
        'wdt' => 'WebProfilerBundle',
    ];

    public function __construct(LabelApi $labelsApi, LoggerInterface $logger)
    {
        $this->labelsApi = $labelsApi;
        $this->logger = $logger;
    }

    /**
     * Get labels from title string.
     * Example title: "[PropertyAccess] [RFC] [WIP] Allow custom methods on property accesses".
     */
    public function extractLabels($title, Repository $repository)
    {
        $labels = [];
        if (preg_match_all('/\[(?P<labels>.+)\]/U', $title, $matches)) {
            $validLabels = $this->getLabels($repository);
            foreach ($matches['labels'] as $label) {
                $label = $this->fixLabelName($label);

                // check case-insensitively, but then apply the correctly-cased label
                if (isset($validLabels[strtolower($label)])) {
                    $labels[] = $validLabels[strtolower($label)];
                }
            }
        }

        $this->logger->debug('Searched for labels in title', ['title' => $title, 'labels' => json_encode($labels)]);

        return $labels;
    }

    public function getAliasesForLabel($label)
    {
        foreach (self::$labelAliases as $alias => $name) {
            if ($name === $label) {
                yield $alias;
            }
        }
    }

    /**
     * Creates a key=>val array, but the key is lowercased.
     *
     * @return array
     */
    private function getLabels(Repository $repository)
    {
        $allLabels = $this->labelsApi->getAllLabelsForRepository($repository);
        $closure = function ($s) {
            return strtolower($s);
        };

        return array_combine(array_map($closure, $allLabels), $allLabels);
    }

    /**
     * It fixes common misspellings and aliases commonly used for label names
     * (e.g. DI -> DependencyInjection).
     */
    private function fixLabelName($label)
    {
        $labelAliases = self::$labelAliases;

        if (isset($labelAliases[strtolower($label)])) {
            return $labelAliases[strtolower($label)];
        }

        return $label;
    }
}
