<?php

namespace App\Subscriber;

use App\Api\Issue\IssueApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Ayyoub AFW-ALLAH <ayyoub.afwallah@gmail.com>
 */
class CongratulateFirstTimeMergedContributorSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly IssueApi $issueApi,
    ) {
    }

    public function onPullRequest(GitHubEvent $event): void
    {
        $data = $event->getData();
        if ('closed' !== $data['action'] || !($data['merged'] ?? false)) {
            return;
        }

        $association = $data['pull_request']['author_association'] ?? '';
        if (!in_array($association, ['NONE', 'FIRST_TIMER', 'FIRST_TIME_CONTRIBUTOR'])) {
            return;
        }

        $repository = $event->getRepository();
        $pullRequestNumber = $data['pull_request']['number'];
        $goodFirstIssueUrl = sprintf(
            'https://github.com/%s/issues?q=is%%3Aissue%%20state%%3Aopen%%20label%%3A%%22Good%%20first%%20issue%%22',
            $repository->getFullName()
        );

        $this->issueApi->commentOnIssue($repository, $pullRequestNumber, <<<TXT
Your first PR just merged. You're officially a Symfony contributor — welcome to the club! 🎉

Whenever you're ready for more, [good first issue]($goodFirstIssueUrl) is a great place to start. Got ideas, found a bug, or just want to help? We're always here.

Carsonbot
TXT
        );

        $event->setResponseData([
            'pull_request' => $pullRequestNumber,
            'first_time_merged_contributor' => true,
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
