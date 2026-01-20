<?php

namespace App\Subscriber;

use App\Api\Issue\IssueApi;
use App\Api\PullRequest\PullRequestApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Ayyoub AFW-ALLAH <ayyoub.afwallah@gmail.com>
 */
class MissingChangelogSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly IssueApi $issueApi,
        private readonly PullRequestApi $pullRequestApi,
    ) {
    }

    public function onPullRequest(GitHubEvent $event): void
    {
        $data = $event->getData();
        if (!in_array($data['action'], ['opened', 'ready_for_review']) || ($data['pull_request']['draft'] ?? false)) {
            return;
        }

        $prBody = $data['pull_request']['body'] ?? '';
        $prNumber = $data['pull_request']['number'];

        // Check if this is a feature PR
        if (!preg_match('/\|\s*New feature\?\s*\|\s*yes\s*/i', $prBody)) {
            return;
        }

        // Check if any CHANGELOG.md file is in the PR
        $repository = $event->getRepository();
        $files = $this->pullRequestApi->getFiles($repository, $prNumber);

        foreach ($files as $file) {
            if (str_ends_with($file, 'CHANGELOG.md')) {
                return;
            }
        }

        $this->issueApi->commentOnIssue($repository, $prNumber, <<<TXT
Hey!

I noticed this is a new feature, but I don't see any changelog file updated in this pull request.

New features should include an entry in the `CHANGELOG.md` file of the patched component(s).

See: https://symfony.com/doc/current/contributing/code/conventions.html#writing-a-changelog-entry

Thanks!

Carsonbot
TXT
        );

        $event->setResponseData([
            'pull_request' => $prNumber,
            'missing_changelog' => true,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            GitHubEvents::PULL_REQUEST => 'onPullRequest',
        ];
    }
}
