<?php

namespace App\Subscriber;

use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Issues\CommentsApiInterface;
use App\Issues\GitHub\CachedLabelsApi;
use App\Repository\Repository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class WelcomeFirstTimeContributorSubscriber implements EventSubscriberInterface
{
    private $commentsApi;

    public function __construct(CommentsApiInterface $commentsApi)
    {

        $this->commentsApi = $commentsApi;
    }
    public function onPullRequest(GitHubEvent $event)
    {
        $data = $event->getData();
        if ('opened' !== $data['action']) {
            return;
        }

        if ('NONE' !== ($data['pull_request']['author_association'] ?? '')) {
            return;
        }

        $repository = $event->getRepository();
        $pullRequestNumber = $data['pull_request']['number'];
        $this->commentsApi->commentOnIssue($repository, $pullRequestNumber, <<<TXT
Hey,

I see that this is your first PR, I just wanted to say that you are awesome. <3

TXT
);

        $event->setResponseData([
            'pull_request' => $pullRequestNumber,
            'new_contributor' => true,
        ]);
    }

    public static function getSubscribedEvents()
    {
        return [
            GitHubEvents::PULL_REQUEST => 'onPullRequest',
        ];
    }
}
