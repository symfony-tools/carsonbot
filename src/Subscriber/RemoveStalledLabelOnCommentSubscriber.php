<?php

namespace App\Subscriber;

use App\Api\Label\LabelApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * If somebody (not the bot) makes a comment on an issue that is stale, then remove
 * the stale label.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class RemoveStalledLabelOnCommentSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LabelApi $labelApi,
        private readonly string $botUsername,
    ) {
    }

    public function onIssueComment(GitHubEvent $event): void
    {
        $data = $event->getData();
        $repository = $event->getRepository();

        // If bot, then nothing.
        if ($data['comment']['user']['login'] === $this->botUsername) {
            return;
        }

        // If not open, then do nothing
        if ('open' !== $data['issue']['state']) {
            return;
        }

        $removed = false;
        $issueNumber = $data['issue']['number'];
        foreach ($data['issue']['labels'] as $label) {
            if ('Stalled' === $label['name']) {
                $removed = true;
                $this->labelApi->removeIssueLabel($issueNumber, 'Stalled', $repository);
            }
        }

        if ($removed) {
            $event->setResponseData([
                'issue' => $issueNumber,
                'removed_stalled_label' => true,
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            GitHubEvents::ISSUE_COMMENT => 'onIssueComment',
        ];
    }
}
