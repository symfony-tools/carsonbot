<?php

namespace App\Subscriber;

use App\Api\Label\CachedLabelsApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Service\LabelNameExtractor;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Looks at new pull requests and auto-labels based on text.
 */
class AutoLabelFromContentSubscriber implements EventSubscriberInterface
{
    private $labelsApi;

    private $labelExtractor;

    public function __construct(CachedLabelsApi $labelsApi, LabelNameExtractor $labelExtractor)
    {
        $this->labelsApi = $labelsApi;
        $this->labelExtractor = $labelExtractor;
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
        foreach ($this->labelExtractor->extractLabels($prTitle, $repository) as $label) {
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
        foreach ($this->labelExtractor->extractLabels($prTitle, $repository) as $label) {
            $labels[] = $label;
        }

        $this->labelsApi->addIssueLabels($issueNumber, $labels, $repository);

        $event->setResponseData([
            'issue' => $issueNumber,
            'issue_labels' => $labels,
        ]);
    }

    public static function getSubscribedEvents()
    {
        return [
            GitHubEvents::PULL_REQUEST => 'onPullRequest',
            GitHubEvents::ISSUES => 'onIssue',
        ];
    }
}
