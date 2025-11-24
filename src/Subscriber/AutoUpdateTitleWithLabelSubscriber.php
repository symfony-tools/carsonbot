<?php

namespace App\Subscriber;

use App\Api\PullRequest\PullRequestApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Service\LabelNameExtractor;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Lock\LockFactory;

/**
 * When a label changed, then update PR title.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class AutoUpdateTitleWithLabelSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LabelNameExtractor $labelExtractor,
        private readonly PullRequestApi $pullRequestApi,
        private readonly LockFactory $lockFactory,
    ) {
    }

    public function onPullRequest(GitHubEvent $event): void
    {
        $data = $event->getData();
        $action = $data['action'];
        if (!in_array($action, ['labeled', 'unlabeled'])) {
            return;
        }

        if (!isset($data['pull_request'])) {
            // Only update PullRequests
            return;
        }

        $repository = $event->getRepository();
        $number = $data['number'];

        sleep(1); // Wait for github API to be updated
        $lock = $this->lockFactory->createLock($repository->getFullName().'#'.$number);
        $lock->acquire(true); // blocking. Lock will be released at __destruct

        // Fetch the current PR just to make sure we are working with all available information
        $githubPullRequest = $this->pullRequestApi->show($repository, $number);
        $originalTitle = $prTitle = trim($githubPullRequest['title'] ?? '');
        $validLabels = [];

        foreach ($githubPullRequest['labels'] ?? [] as $label) {
            if ('dddddd' === strtolower($label['color'])) {
                $validLabels[] = $label['name'];
                // Remove label name from title
                $prTitle = str_ireplace('['.$label['name'].']', '', $prTitle);

                // Remove label aliases from title
                foreach ($this->labelExtractor->getAliasesForLabel($label['name']) as $alias) {
                    $prTitle = str_ireplace('['.$alias.']', '', $prTitle);
                }
            }
        }

        // Remove any other labels in the title.
        foreach ($this->labelExtractor->extractLabels($prTitle, $repository) as $label) {
            $prTitle = str_ireplace('['.$label.']', '', $prTitle);
        }

        sort($validLabels);
        $prPrefix = '';
        foreach ($validLabels as $label) {
            $prPrefix .= '['.$label.']';
        }

        // Clean string from all HTML chars and remove whitespace at the beginning
        $prTitle = (string) preg_replace('@^[\h\s]+@u', '', html_entity_decode($prTitle));

        // Extract any bracketed text at the beginning of the title
        $leadingBrackets = '';
        $remainingTitle = $prTitle;

        // Match all consecutive bracketed items at the start of the title
        while (preg_match('/^\[([^]]+)]\s*/', $remainingTitle, $matches)) {
            $leadingBrackets .= '['.$matches[1].']';
            $remainingTitle = substr($remainingTitle, strlen($matches[0]));
        }

        // Combine: valid labels + any unrecognized brackets + remaining title
        if ('' !== trim($remainingTitle)) {
            $prTitle = $prPrefix.$leadingBrackets.' '.trim($remainingTitle);
        } else {
            $prTitle = $prPrefix.$leadingBrackets;
        }

        if ('symfony/ai' === $repository->getFullName()) {
            $prTitle = preg_replace('/\[ai[\s\-]*bundle\]/i', '[AI Bundle]', $prTitle) ?? $prTitle;
            $prTitle = preg_replace('/\[mcp[\s\-]*bundle\]/i', '[MCP Bundle]', $prTitle) ?? $prTitle;
        }

        if ($originalTitle === $prTitle) {
            return;
        }

        $this->pullRequestApi->updateTitle($repository, $number, $prTitle);
        $event->setResponseData([
            'pull_request' => $number,
            'new_title' => $prTitle,
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
