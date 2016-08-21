<?php

namespace AppBundle\Issues;

use AppBundle\Event\GitHubEvent;
use AppBundle\Repository\Provider\RepositoryProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Handles GitHub webhook requests.
 *
 * @author Jules Pietri <jules@heahprod.com>
 */
class GitHubRequestHandler
{
    private $dispatcher;
    private $repositoryProvider;
    private $container;
    private $logger;

    public function __construct(EventDispatcherInterface $dispatcher, RepositoryProviderInterface $repositoryProvider, ContainerInterface $container, LoggerInterface $logger)
    {
        $this->dispatcher = $dispatcher;
        $this->repositoryProvider = $repositoryProvider;
        $this->container = $container;
        $this->logger = $logger;
    }

    /**
     * @param Request $request
     *
     * @return array The response data
     */
    public function handle(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        if (null === $data) {
            throw new BadRequestHttpException('Invalid JSON body!');
        }

        $repositoryFullName = isset($data['repository']['full_name']) ? $data['repository']['full_name'] : null;
        if (empty($repositoryFullName)) {
            throw new BadRequestHttpException('No repository name!');
        }

        $this->logger->debug(sprintf('Handling from repository %s', $repositoryFullName));

        $repository = $this->repositoryProvider->getRepository($repositoryFullName);

        if (!$repository) {
            throw new PreconditionFailedHttpException(sprintf('Unsupported repository "%s".', $repositoryFullName));
        }

        if ($repository->getSecret()) {
            if (!$request->headers->has('X-Hub-Signature')) {
                throw new AccessDeniedException('The request is not secured.');
            }
            if (!$this->authenticate($request->headers->get('X-Hub-Signature'), $repository->getSecret(), $request->getContent())) {
                throw new AccessDeniedException('Invalid signature.');
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
            throw new \RuntimeException(sprintf('Failed dispatching "%s" event for "%s" repository.', $eventName, $repository->getFullName()), 0, $e);
        }

        $responseData = $event->getResponseData();

        return $responseData;
    }

    private function authenticate($hash, $key, $data)
    {
        if (!extension_loaded('hash')) {
            throw new \RuntimeException('"hash" extension is needed to check request signature.');
        }

        return hash_equals($hash, 'sha1='.hash_hmac('sha1', $data, $key));
    }
}
