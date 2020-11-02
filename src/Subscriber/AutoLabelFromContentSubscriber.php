<?php

namespace App\Subscriber;

use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Issues\GitHub\CachedLabelsApi;
use App\Repository\Repository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Looks at new pull requests and auto-labels based on text.
 */
class AutoLabelFromContentSubscriber implements EventSubscriberInterface
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

    public function onPullRequest(GitHubEvent $event)
    {
        $data = $event->getData();
        if ('opened' !== $action = $data['action']) {
            return;
        }
        $repository = $event->getRepository();

        $prNumber = $data['pull_request']['number'];
        $prTitle = $data['pull_request']['title'];
        $prBody = $data['pull_request']['body'];
        $prLabels = [];

        // the PR title usually contains one or more labels
        foreach ($this->extractLabels($prTitle, $repository) as $label) {
            $prLabels[] = $label;
        }

        // the PR body usually indicates if this is a Bug, Feature, BC Break or Deprecation
        if (preg_match('/\|\s*Bug fix\?\s*\|\s*yes\s*/i', $prBody, $matches)) {
            $prLabels[] = 'Bug';
        }
        if (preg_match('/\|\s*New feature\?\s*\|\s*yes\s*/i', $prBody, $matches)) {
            $prLabels[] = 'Feature';
        }
        if (preg_match('/\|\s*BC breaks\?\s*\|\s*yes\s*/i', $prBody, $matches)) {
            $prLabels[] = 'BC Break';
        }
        if (preg_match('/\|\s*Deprecations\?\s*\|\s*yes\s*/i', $prBody, $matches)) {
            $prLabels[] = 'Deprecation';
        }

        $this->labelsApi->addIssueLabels($prNumber, $prLabels, $repository);

        $event->setResponseData([
            'pull_request' => $prNumber,
            'pr_labels' => $prLabels,
        ]);
    }

    public function onIssue(GitHubEvent $event)
    {
        $data = $event->getData();
        if ('opened' !== $action = $data['action']) {
            return;
        }
        $repository = $event->getRepository();

        $issueNumber = $data['issue']['number'];
        $prTitle = $data['issue']['title'];
        $labels = [];

        // the issue title usually contains one or more labels
        foreach ($this->extractLabels($prTitle, $repository) as $label) {
            $labels[] = $label;
        }

        $this->labelsApi->addIssueLabels($issueNumber, $labels, $repository);

        $event->setResponseData([
            'issue' => $issueNumber,
            'issue_labels' => $labels,
        ]);
    }

    private function extractLabels($title, Repository $repository)
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

    public static function getSubscribedEvents()
    {
        return [
            GitHubEvents::PULL_REQUEST => 'onPullRequest',
            GitHubEvents::ISSUES => 'onIssue',
        ];
    }
}
