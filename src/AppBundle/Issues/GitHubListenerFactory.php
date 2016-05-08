<?php

namespace AppBundle\Issues;

use AppBundle\Issues\GitHub\CachedLabelsApi;
use AppBundle\Issues\GitHub\GitHubStatusApi;
use Github\Api\Issue\Labels;

/**
 * Class GitHubListenerFactory.
 *
 * @author Jules Pietri <jules@heahprod.com>
 */
class GitHubListenerFactory
{
    private $labelsApi;
    private $listenerClasses;

    public function __construct(Labels $labelsApi, array $listenerClasses)
    {
        $this->labelsApi = $labelsApi;
        $this->listenerClasses = $listenerClasses;
    }

    public function createFromRepository($repository)
    {
        if (!isset($this->listenerClasses[$repository])) {
            throw new \InvalidArgumentException(sprintf('Unsupported repository "%s". Supported repositories are: "%s".', $repository, implode('", "', array_keys($this->listenerClasses))));
        }

        $listener = $this->listenerClasses[$repository];

        return new $listener(
            new GitHubStatusApi(
                new CachedLabelsApi($this->labelsApi, $repository),
                $repository
            )
        );
    }
}
