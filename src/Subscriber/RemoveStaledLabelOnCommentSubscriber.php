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
class RemoveStaledLabelOnCommentSubscriber implements EventSubscriberInterface
{
    private $labelApi;
    private $botUsername;

    public function __construct(LabelApi $labelApi, string $botUsername)
    {
        $this->labelApi = $labelApi;
        $this->botUsername = $botUsername;
    }

    public function onIssueComment(GitHubEvent $event)
    {
        $data = $event->getData();
        $repository = $event->getRepository();

        // If bot, then nothing.
        if ($data['comment']['user']['login'] === $this->botUsername) {
            return;
        }

        $removed = false;
        $issueNumber = $data['issue']['number'];
        foreach ($data['issue']['labels'] as $label) {
            if ('Staled' === $label['name']) {
                $removed = true;
                $this->labelApi->removeIssueLabel($issueNumber, 'Staled', $repository);
            }
        }

        if ($removed) {
            $event->setResponseData([
                'issue' => $issueNumber,
                'removed_staled_label' => true,
            ]);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            GitHubEvents::ISSUE_COMMENT => 'onIssueComment',
        ];
    }
}
