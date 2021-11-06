<?php

namespace App\Subscriber;

use App\Api\Issue\IssueApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\String\UnicodeString;

/**
 * @author Antoine Makdessi <amakdessi@me.com>
 */
class MismatchBranchDescriptionSubscriber implements EventSubscriberInterface
{
    private IssueApi $issueApi;
    private LoggerInterface $logger;

    public function __construct(IssueApi $issueApi, LoggerInterface $logger)
    {
        $this->issueApi = $issueApi;
        $this->logger = $logger;
    }

    public function onPullRequest(GitHubEvent $event)
    {
        $data = $event->getData();
        if (!in_array($data['action'], ['opened', 'ready_for_review']) || ($data['pull_request']['draft'] ?? false)) {
            return;
        }

        $number = $data['pull_request']['number'];

        $descriptionBranch = $this->extractDescriptionBranchFromBody($data['pull_request']['body']);
        if (null === $descriptionBranch) {
            $this->logger->notice('Pull Request without default template.', ['pull_request_number' => $number]);
            return;
        }

        $targetBranch = $data['pull_request']['base']['ref'];
        if ($targetBranch === $descriptionBranch) {
            return;
        }

        $this->issueApi->commentOnIssue($event->getRepository(), $number, <<<TXT
Hey!

Thanks for your PR. You are targeting branch "$targetBranch" but it seems your PR description refers to branch "$descriptionBranch".
Could you update the PR description please? This helps core maintainers a lot.

Cheers!

Carsonbot
TXT
);

        $event->setResponseData([
            'pull_request' => $number,
        ]);
    }

    public static function getSubscribedEvents()
    {
        return [
            GitHubEvents::PULL_REQUEST => 'onPullRequest',
        ];
    }

    private function extractDescriptionBranchFromBody(string $body): ?string
    {
        $s = new UnicodeString($body);

        // @see symfony/symfony/.github/PULL_REQUEST_TEMPLATE.md
        if (!$s->containsAny('Branch?')) {
            return null;
        }

        $descriptionBranch = $s->match('/^.*Branch.*$/');
        $descriptionBranch = $descriptionBranch[0]; // row matching
        $descriptionBranch = \explode('|', $descriptionBranch)[2]; // get the version

        return \trim($descriptionBranch);
    }
}
