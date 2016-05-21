<?php

namespace AppBundle\Issues;

use AppBundle\Event\GitHubEvent;
use AppBundle\Exception\GitHubAccessDeniedException;
use AppBundle\Exception\GitHubBadRequestException;
use AppBundle\Exception\GitHubExceptionInterface;
use AppBundle\Exception\GitHubInvalidConfigurationException;
use AppBundle\Exception\GitHubPreconditionFailedException;
use AppBundle\Exception\GitHubRuntimeException;
use AppBundle\Issues\GitHub\CachedLabelsApi;
use AppBundle\Issues\GitHub\GitHubStatusApi;
use AppBundle\Repository\Provider\RepositoryProviderInterface;
use AppBundle\Repository\RepositoryRegistry;
use Github\Api\Issue\Labels;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles GitHub webhook requests
 *
 * @author Jules Pietri <jules@heahprod.com>
 */
class GitHubRequestHandler
{
    private $labelsApi;
    private $dispatcher;
    private $repositoryProvider;
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(Labels $labelsApi, EventDispatcherInterface $dispatcher, RepositoryProviderInterface $repositoryProvider, ContainerInterface $container)
    {
        $this->labelsApi = $labelsApi;
        $this->dispatcher = $dispatcher;
        $this->repositoryProvider = $repositoryProvider;
        $this->container = $container;
    }

    /**
     * @param Request $request
     * @return array The response data
     *
     * @throws GitHubExceptionInterface When the request or the configuration are invalid
     */
    public function handle(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        if (null === $data) {
            throw new GitHubBadRequestException('Invalid JSON body!');
        }

        $repositoryFullName = isset($data['repository']['full_name']) ? $data['repository']['full_name'] : null;
        if (empty($repositoryFullName)) {
            throw new GitHubBadRequestException('No repository name!');
        }

        $repository = $this->repositoryProvider->getRepository($repositoryFullName);

        if (!$repository) {
            throw new GitHubPreconditionFailedException(sprintf('Unsupported repository "%s".', $repositoryFullName));
        }

        if ($repository->getSecret()) {
            if (!$request->headers->has('X-Hub-Signature')) {
                throw new GitHubAccessDeniedException('The request is not secured.');
            }
            if (!$this->authenticate($request->headers->get('X-Hub-Signature'), $repository->getSecret(), $request->getContent())) {
                throw new GitHubAccessDeniedException('Invalid signature.');
            }
        }

        foreach ($repository->getSubscribers() as $subscriberId) {
            $this->dispatcher->addSubscriber($this->container->get($subscriberId));
        }

        $event = new GitHubEvent($data, $repository);
        $eventName = $request->headers->get('X-Github-Event');

        try {
            $this->dispatcher->dispatch('github.'.$eventName, $event);
        } catch (\Exception $e) {
            throw new GitHubRuntimeException(sprintf('Failed dispatching "%s" event for "%s" repository.', $eventName, $repository), 0, $e);
        }

        $responseData = $event->getResponseData();

        if (empty($responseData)) {
            throw new GitHubPreconditionFailedException(sprintf('Unsupported event "%s"', $eventName));
        }

        return $responseData;
    }

    private function authenticate($hash, $key, $data)
    {
        if (!extension_loaded('hash')) {
            throw new GitHubInvalidConfigurationException('"hash" extension is needed to check request signature.');
        }

        return $hash !== 'sha1='.hash_hmac('sha1', $data, $key);
    }
}
