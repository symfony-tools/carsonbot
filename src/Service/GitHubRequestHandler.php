<?php

namespace App\Service;

use App\Event\EventDispatcher;
use App\Event\GitHubEvent;
use App\Service\RepositoryProvider;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

/**
 * Handles GitHub webhook requests.
 *
 * @author Jules Pietri <jules@heahprod.com>
 */
class GitHubRequestHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    private $dispatcher;
    private $repositoryProvider;
    private $container;

    public function __construct(EventDispatcher $dispatcher, RepositoryProvider $repositoryProvider)
    {
        $this->dispatcher = $dispatcher;
        $this->repositoryProvider = $repositoryProvider;
    }

    /**
     * @return array The response data
     */
    public function handle(Request $request)
    {
        $data = json_decode((string) $request->getContent(), true);
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

        if (!empty($repository->getSecret())) {
            if (!$request->headers->has('X-Hub-Signature')) {
                throw new AccessDeniedHttpException('The request is not secured.');
            }
            if (!$this->authenticate($request->headers->get('X-Hub-Signature'), $repository->getSecret(), $request->getContent())) {
                throw new AccessDeniedHttpException('Invalid signature.');
            }
        }

        $event = new GitHubEvent($data, $repository);
        $eventName = $request->headers->get('X-Github-Event');

        try {
            $this->dispatcher->dispatch($event, 'github.'.$eventName);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Failed dispatching "%s" event for "%s" repository.', (string) $eventName, $repository->getFullName()), 0, $e);
        }

        $responseData = $event->getResponseData();

        if (empty($responseData)) {
            $responseData['unsupported_action'] = $eventName;
        }

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
