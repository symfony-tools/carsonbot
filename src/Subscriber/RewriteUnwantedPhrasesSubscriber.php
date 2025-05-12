<?php

namespace App\Subscriber;

use App\Api\PullRequest\PullRequestApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Lock\LockFactory;

/**
 * There are some phrases that should be avoided in title/description. This
 * subscriber helps rewrite those.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class RewriteUnwantedPhrasesSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly PullRequestApi $pullRequestApi,
        private readonly LockFactory $lockFactory,
    ) {
    }

    public function onPullRequest(GitHubEvent $event): void
    {
        $data = $event->getData();
        $action = $data['action'];
        if (!in_array($action, ['opened', 'ready_for_review', 'edited'])) {
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
        $title = $this->replaceUnwantedPhrases($githubPullRequest['title'] ?? '', $a);
        $body = $this->replaceUnwantedPhrases($githubPullRequest['body'] ?? '', $b);

        if (0 === $a + $b) {
            // No changes
            return;
        }

        $this->pullRequestApi->updateTitle($repository, $number, $title, $body);
        $event->setResponseData([
            'pull_request' => $number,
            'unwanted_phrases' => 'rewritten',
        ]);
    }

    /**
     * @param int<0, max>|null &$count
     *
     * @param-out int $count
     */
    private function replaceUnwantedPhrases(string $text, &$count): string
    {
        $replace = [
            'dead code' => 'unused code',
        ];

        return str_ireplace(array_keys($replace), array_values($replace), $text, $count);
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
