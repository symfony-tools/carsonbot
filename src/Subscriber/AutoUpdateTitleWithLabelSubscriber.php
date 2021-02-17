<?php

namespace App\Subscriber;

use App\Api\Label\LabelApi;
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
    private $labelsApi;

    private $labelExtractor;
    private $pullRequestApi;
    private $lockFactory;

    public function __construct(LabelApi $labelsApi, LabelNameExtractor $labelExtractor, PullRequestApi $pullRequestApi, LockFactory $lockFactory)
    {
        $this->labelsApi = $labelsApi;
        $this->labelExtractor = $labelExtractor;
        $this->pullRequestApi = $pullRequestApi;
        $this->lockFactory = $lockFactory;
    }

    public function onPullRequest(GitHubEvent $event)
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

        $lock = $this->lockFactory->createLock($repository->getFullName().'#'.$number);
        $lock->acquire(true); // blocking. Lock will be released at __destruct

        $originalTitle = $prTitle = trim($data['pull_request']['title']);
        $validLabels = [];
        foreach ($data['pull_request']['labels'] as $label) {
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

        // Add back labels
        $prTitle = trim($prPrefix.' '.trim($prTitle));
        if ($originalTitle === $prTitle) {
            return;
        }

        // Refetch the current title just to make sure it has not changed
        if ($prTitle === ($this->pullRequestApi->show($repository, $number)['title'] ?? '')) {
            return;
        }

        $this->pullRequestApi->updateTitle($repository, $number, $prTitle);
        $event->setResponseData([
            'pull_request' => $number,
            'new_title' => $prTitle,
        ]);
    }

    public static function getSubscribedEvents()
    {
        return [
            GitHubEvents::PULL_REQUEST => 'onPullRequest',
        ];
    }
}
