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
 * @author Antoine Lamirault <lamiraultantoine@gmail.com>
 */
class MismatchBranchDescriptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly IssueApi $issueApi,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function onPullRequest(GitHubEvent $event): void
    {
        $data = $event->getData();
        if (!in_array($data['action'], ['opened', 'ready_for_review', 'edited']) || ($data['pull_request']['draft'] ?? false)) {
            return;
        }

        $number = $data['pull_request']['number'];

        $descriptionBranch = $this->extractDescriptionBranchFromBody($data['pull_request']['body']);
        if (null === $descriptionBranch) {
            $this->logger->notice('Pull Request without default template.', ['pull_request_number' => $number]);

            return;
        }

        $targetBranch = $data['pull_request']['base']['ref'];
        $commentId = $this->issueApi->findBotComment($event->getRepository(), $number, 'seems your PR description refers to branch');

        if ($targetBranch === $descriptionBranch) {
            if ($commentId) {
                $this->issueApi->removeComment($event->getRepository(), $commentId);
            }

            return;
        }

        // Avoid duplicate comments
        if ($commentId) {
            return;
        }

        $this->issueApi->commentOnIssue($event->getRepository(), $number, <<<TXT
Hey!

Thanks for your PR. You are targeting branch "$targetBranch" but it seems your PR description refers to branch "$descriptionBranch".
Could you update the PR description or change target branch? This helps core maintainers a lot.

Cheers!

Carsonbot
TXT
        );

        $event->setResponseData([
            'pull_request' => $number,
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

    private function extractDescriptionBranchFromBody(string $body): ?string
    {
        $s = new UnicodeString($body);
        $bodyWithoutComment = $s->replaceMatches('/<!--\s*.*\s*-->/', '');

        // @see symfony/symfony/.github/PULL_REQUEST_TEMPLATE.md
        if (!$bodyWithoutComment->containsAny('Branch?')) {
            return null;
        }

        $rowsDescriptionBranch = $bodyWithoutComment->match('/.*Branch.*/');

        $rowDescriptionBranch = $rowsDescriptionBranch[0]; // row matching

        $descriptionBranchParts = \explode('|', $rowDescriptionBranch);
        if (false === array_key_exists(2, $descriptionBranchParts)) { // Branch description is in second Markdown table column
            return null;
        }

        $descriptionBranch = $descriptionBranchParts[2]; // get the version

        return \trim($descriptionBranch);
    }
}
