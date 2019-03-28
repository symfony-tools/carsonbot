<?php

declare(strict_types=1);

namespace AppBundle\Subscriber;

use AppBundle\Event\GitHubEvent;
use AppBundle\GitHubEvents;
use AppBundle\Issues\GitHub\CachedLabelsApi;
use AppBundle\Repository\Repository;
use Github\Api\Issue as IssueApi;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class WriteIssueOnDocumentationSubscriber implements EventSubscriberInterface
{
    /**
     * @var CachedLabelsApi
     */
    private $labelsApi;

    /**
     * @var IssueApi
     */
    private $issueApi;

    public function __construct(CachedLabelsApi $labelsApi, IssueApi $issueApi)
    {
        $this->labelsApi = $labelsApi;
        $this->labelsApi = $issueApi;
    }

    public function onPullRequest(GitHubEvent $event)
    {
        $data = $event->getData();
        if ('closed' !== $data['action'] || false === $data['pull_request']['merged']) {
            $event->setResponseData(array('unsupported_action' => $data['action']));

            return;
        }

        $repository = $event->getRepository();
        $pullRequestNumber = $data['pull_request']['number'];

        // Assert: PullRequest was merged.
        if (!$this->hasFeatureLabel($repository, $pullRequestNumber)) {
            $event->setResponseData(array('unsupported_reason' => 'PR does not have "Feature" label.'));

            return;
        }

        // Parse PR body to see if there is a reference to docs
        $body = $data['pull_request']['number'];
        if (strstr($body, 'github.com/symfony/symfony-docs')) {
            $event->setResponseData(array('unsupported_reason' => 'PR have a reference to github.com/symfony/symfony-docs.'));

            return;
        }

        // Create issue
        $author = $data['pull_request']['user']['login'];
        $title = $data['pull_request']['title'];
        $pullRequestUrl =  $data['pull_request']['url'];
        $issueContent = $this->getIssueContent($author, $pullRequestUrl);

        $issueData = $this->issueApi->create('symfony', 'symfony-docs', [
            'title' => sprintf('Add docs for: %s', substr($title, 0, 55)),
            'body'=>
<<<TEXT
| Q          | A
| ---------- | ---
| Feature PR | $pullRequestUrl
| PR author  | @$author

$issueContent

TEXT,
        ]);


        $event->setResponseData(array(
            'pull_request' => $pullRequestNumber,
            'action' => 'Created issue on docs: '.$issueData['url'],
        ));
    }

    /**
     * Lets be fun, encouraging and helpful
     */
    private function getIssueContent($author, $url)
    {
        $messages = [
            "Thank you @$author! Could you please add some documentation too?",
            "That feature looks great. Could you care to add some docs, please?",
            'Do not forget to add documentation to your features.',
            "I did a quick review on @$author's PR. I like it. But we should add some documentation, right?'",
            'What is a great feature without documentation =)',
            "I saw [this]($url) and thought: \"This is great, lets make sure everybody knows about it\".",
            "@$author, You are the BEST! Could you also add some documentation to this feature?",
            "I really like $url, But I think it needs some documentation, right?",
            "This is just a reminder for @$author (or anyone) to create some documentation to this feature.",
            "I would really love to see this documented.",
            'That feature would look better with some documentation',
            "The only think that could make @$author's PR a little bit better is some documentation. Could someone please help out?'",
            "I like the work done in $url, but we should add some documentation about it",
        ];

        $idx = rand(0, count($messages) - 1);

        return $messages[$idx];
    }

    private function hasFeatureLabel(Repository $repository, $pullRequestNumber)
    {
        foreach ($this->labelsApi->getIssueLabels($pullRequestNumber, $repository) as $label) {
            if ('Feature' === $label) {
                return true;
            }
        }

        return false;
    }

    public static function getSubscribedEvents()
    {
        return array(
            GitHubEvents::PULL_REQUEST => 'onPullRequest',
        );
    }
}
