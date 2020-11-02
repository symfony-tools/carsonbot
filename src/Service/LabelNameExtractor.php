<?php

namespace App\Service;

use App\Issues\GitHub\CachedLabelsApi;
use App\Repository\Repository;

/**
 * Extract label name from a PR/Issue.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class LabelNameExtractor
{
    private $labelsApi;

    private static $labelAliases = [
        'di' => 'DependencyInjection',
        'bridge\twig' => 'TwigBridge',
        'router' => 'Routing',
        'translation' => 'Translator',
        'twig bridge' => 'TwigBridge',
        'wdt' => 'WebProfilerBundle',
        'profiler' => 'WebProfilerBundle',
    ];

    public function __construct(CachedLabelsApi $labelsApi)
    {
        $this->labelsApi = $labelsApi;
    }

    /**
     * Get labels from title string.
     */
    public function extractLabels($title, Repository $repository)
    {
        $labels = [];

        // e.g. "[PropertyAccess] [RFC] [WIP] Allow custom methods on property accesses"
        if (preg_match_all('/\[(?P<labels>.+)\]/U', $title, $matches)) {
            // creates a key=>val array, but the key is lowercased
            $allLabels = $this->labelsApi->getAllLabelsForRepository($repository);
            $validLabels = array_combine(
                array_map(function ($s) {
                    return strtolower($s);
                }, $allLabels),
                $allLabels
            );

            foreach ($matches['labels'] as $label) {
                $label = $this->fixLabelName($label);

                // check case-insensitively, but the apply the correctly-cased label
                if (isset($validLabels[strtolower($label)])) {
                    $labels[] = $validLabels[strtolower($label)];
                }
            }
        }

        return $labels;
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
