<?php

namespace App\Subscriber;

use App\Api\Label\LabelApi;
use App\Event\GitHubEvent;
use App\GitHubEvents;
use App\Service\LabelNameExtractor;
use Github\Api\PullRequest;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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

    public function __construct(LabelApi $labelsApi, LabelNameExtractor $labelExtractor, PullRequest $pullRequestApi)
    {
        $this->labelsApi = $labelsApi;
        $this->labelExtractor = $labelExtractor;
        $this->pullRequestApi = $pullRequestApi;
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

        $originalTitle = $prTitle = trim($data['pull_request']['title']);
        $validLabels = [];
        foreach ($data['pull_request']['labels'] as $label) {
            if ('dddddd' === strtolower($label['color'])) {
                $validLabels[] = $label['name'];
                // Remove label name from title
                $prTitle = str_replace('['.$label['name'].']', '', $prTitle);

                // Remove label aliases from title
                foreach ($this->labelExtractor->getAliasesForLabel($label['name']) as $alias) {
                    $prTitle = str_replace('['.$alias.']', '', $prTitle);
                }
            }
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

        $repository = $event->getRepository();
        $prNumber = $data['number'];
        $this->pullRequestApi->update($repository->getVendor(), $repository->getName(), $prNumber, ['title' => $prTitle]);
        $event->setResponseData([
            'pull_request' => $prNumber,
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
