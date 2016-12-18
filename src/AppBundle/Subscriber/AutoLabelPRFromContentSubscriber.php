<?php

namespace AppBundle\Subscriber;

use AppBundle\Event\GitHubEvent;
use AppBundle\GitHubEvents;
use AppBundle\Issues\GitHub\CachedLabelsApi;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Looks at new pull requests and auto-labels based on text.
 */
class AutoLabelPRFromContentSubscriber implements EventSubscriberInterface
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
     * @param GitHubEvent $event
     */
    public function onPullRequest(GitHubEvent $event)
    {
        $data = $event->getData();
        if ('opened' !== $action = $data['action']) {
            $event->setResponseData(array('unsupported_action' => $action));

            return;
        }

        $prNumber = $data['pull_request']['number'];
        $prTitle = $data['pull_request']['title'];
        $prBody = $data['pull_request']['body'];
        $prLabels = array();

        // the PR title usually contains one or more labels
        foreach ($this->extractLabels($prTitle) as $label) {
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

        $this->labelsApi->addIssueLabels($prNumber, $prLabels, $event->getRepository());

        $event->setResponseData(array(
            'pull_request' => $prNumber,
            'pr_labels' => $prLabels,
        ));
    }

    private function extractLabels($prTitle)
    {
        $labels = array();

        // e.g. "[PropertyAccess] [RFC] [WIP] Allow custom methods on property accesses"
        if (preg_match_all('/\[(?P<labels>.+)\]/U', $prTitle, $matches)) {
            // creates a key=>val array, but the key is lowercased
            $validLabels = array_combine(
                array_map(function($s) {
                    return strtolower($s);
                }, $this->getValidLabels()),
                $this->getValidLabels()
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
     * TODO: get valid labels from the repository via GitHub API.
     */
    private function getValidLabels()
    {
        $realLabels = array(
            'Asset', 'BC Break', 'BrowserKit', 'Bug', 'Cache', 'ClassLoader',
            'Config', 'Console', 'Critical', 'CssSelector', 'Debug', 'DebugBundle',
            'DependencyInjection', 'Deprecation', 'Doctrine', 'DoctrineBridge',
            'DomCrawler', 'Drupal related', 'DX', 'Easy Pick', 'Enhancement',
            'EventDispatcher', 'ExpressionLanguage', 'Feature', 'Filesystem',
            'Finder', 'Form', 'FrameworkBundle', 'HttpFoundation', 'HttpKernel',
            'Intl', 'Ldap', 'Locale', 'MonologBridge', 'OptionsResolver',
            'PhpUnitBridge', 'Process', 'PropertyAccess', 'PropertyInfo', 'Ready',
            'RFC', 'Routing', 'Security', 'SecurityBundle', 'Serializer',
            'Stopwatch', 'Templating', 'Translator', 'TwigBridge', 'TwigBundle',
            'Unconfirmed', 'Validator', 'VarDumper', 'WebProfilerBundle', 'Workflow',
            'Yaml',
        );

        return array_merge(
            $realLabels,
            // also consider the "aliases" as valid, so they are used
            array_keys(self::$labelAliases)
        );
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
        return array(
            GitHubEvents::PULL_REQUEST => 'onPullRequest',
        );
    }
}
