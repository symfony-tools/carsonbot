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
 * Write an issue on the documentation repository when a PullRequest with label "Feature" is merged.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class WriteIssueOnMergedFeatureSubscriber implements EventSubscriberInterface
{
    /**
     * @var CachedLabelsApi
     */
    private $labelsApi;

    /**
     * @var IssueApi
     */
    private $issueApi;

    /**
     * @var string "user/repository"
     */
    private $targetRepository;

    public function __construct(CachedLabelsApi $labelsApi, IssueApi $issueApi, $targetRepository)
    {
        $this->labelsApi = $labelsApi;
        $this->issueApi = $issueApi;
        $this->targetRepository = $targetRepository;

        if (false === strpos($this->targetRepository, '/')) {
            throw new \LogicException('Third parameter of WriteIssueOnDocumentationSubscriber must be a repository on format "symfony/symfony-docs"');
        }
    }

    public function onPullRequest(GitHubEvent $event)
    {
        $data = $event->getData();
        if ('closed' !== $data['action'] || false === $data['pull_request']['merged']) {
            $event->setResponseData(array('unsupported_action' => $data['action']));

            return;
        }

        // Assert: PullRequest was merged.

        $repository = $event->getRepository();
        $pullRequestNumber = $data['pull_request']['number'];

        if (!$this->hasFeatureLabel($repository, $pullRequestNumber)) {
            $event->setResponseData(array('unsupported_reason' => 'PR does not have "Feature" label.'));

            return;
        }

        // Parse PR body to see if there is a reference to docs
        $body = $data['pull_request']['body'];
        if (strstr($body, $this->targetRepository)) {
            $event->setResponseData(array('unsupported_reason' => 'PR have a reference to '.$this->targetRepository));

            return;
        }

        // Create issue
        $author = $data['pull_request']['user']['login'];
        $title = $data['pull_request']['title'];
        $pullRequestUrl =  $data['pull_request']['html_url'];
        $issueContent = $this->getIssueContent($author, $pullRequestUrl);
        list($targetUser, $targetRepo) = explode('/', $this->targetRepository, 2);

        $issueData = $this->issueApi->create($targetUser, $targetRepo, [
            'title' => sprintf('Add docs for: %s', strlen($title) > 56 ? substr($title, 0, 55).'..' : $title),
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
            'action' => 'Created issue on docs: '.$issueData['html_url'],
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
            "Do not forget to add documentation to your features.",
            "I did a quick review on @$author's PR. I like it. But we should add some documentation, right?",
            "What is a great feature without documentation =)",
            "I saw [this]($url) and thought: \"This is great, lets make sure everybody knows about it\".",
            "@$author, You are the BEST! Could you also add some documentation to this feature?",
            "I really like $url, But I think it needs some documentation, right?",
            "This is just a reminder for @$author (or anyone) to create some documentation to this feature.",
            "I would really love to see this documented.",
            "That feature would look better with some documentation",
            "The only thing that could make @$author's PR a little bit better is some documentation. Could someone please help out?'",
            "I like the work done in $url, but we should add some documentation about it",
            "There is a rumor on the internet saying that @$author has created a really cool feature. We should add some docs about it.",
            "Is there anyone that want to help with writing documentation for @$author's PR?",
            "There are so many good things coming form @$author. We should not forget to write some documentation.",
            "It is funny, just the other week I was thinking on a feature like [this]($url). Could we help adding some docs?",
            "Wohoo, [this PR]($url) just got merged. We need to add a few lines in the docs, right?",
            "Excellent work @$author. Lets keep it up by adding some docs. =)",
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
