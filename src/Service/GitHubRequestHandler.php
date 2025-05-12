<?php

namespace App\Service;

use App\Event\EventDispatcher;
use App\Event\GitHubEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

/**
 * Handles GitHub webhook requests.
 *
 * @author Jules Pietri <jules@heahprod.com>
 */
class GitHubRequestHandler
{
    public function __construct(
        private readonly EventDispatcher $dispatcher,
        private readonly RepositoryProvider $repositoryProvider,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<string, mixed> The response data
     */
    public function handle(Request $request): array
    {
        $data = json_decode($request->getContent(), true);
        if (null === $data) {
            throw new BadRequestHttpException('Invalid JSON body!');
        }

        $repositoryFullName = $data['repository']['full_name'] ?? null;
        if (empty($repositoryFullName)) {
            throw new BadRequestHttpException('No repository name!');
        }

        $this->logger->debug(sprintf('Handling from repository %s', $repositoryFullName));

        $repository = $this->repositoryProvider->getRepository($repositoryFullName);
        if (!$repository) {
            throw new PreconditionFailedHttpException(sprintf('Unsupported repository "%s".', $repositoryFullName));
        }

        $secret = $repository->getSecret();
        if (is_string($secret) && '' !== trim($secret)) {
            if (!$request->headers->has('X-Hub-Signature')) {
                throw new AccessDeniedHttpException('The request is not secured.');
            }

            $content = $request->getContent();
            if (!$content) {
                throw new BadRequestHttpException('Empty request body!');
            }

            $signature = $request->headers->get('X-Hub-Signature');
            if (!$signature) {
                throw new BadRequestHttpException('Invalid signature!');
            }

            if (!$this->authenticate($signature, $secret, $content)) {
                throw new AccessDeniedHttpException('Invalid signature.');
            }
        }

        $event = new GitHubEvent($data, $repository);
        $eventName = $request->headers->get('X-Github-Event');

        try {
            $this->dispatcher->dispatch($event, 'github.'.$eventName);
        } catch (\Exception $e) {
            $message = sprintf('Failed dispatching "%s" event for "%s" repository.', (string) $eventName, $repository->getFullName());
            $this->logger->error($message, ['exception' => $e]);

            throw new \RuntimeException($message, 0, $e);
        }

        $responseData = $event->getResponseData();

        if (empty($responseData)) {
            $responseData['unsupported_action'] = $eventName;
        }

        $this->logger->info('Done handling request', [
            'event' => $eventName,
            'action' => $data['action'] ?? 'unknown',
            'response-data' => json_encode($responseData),
            'repository' => $repositoryFullName,
            'issue-number' => $data['number'] ?? $data['issue']['number'] ?? $data['pull_request']['number'] ?? 'unknown',
        ]);

        return $responseData;
    }

    private function authenticate(string $hash, string $key, string $data): bool
    {
        if (!extension_loaded('hash')) {
            throw new \RuntimeException('"hash" extension is needed to check request signature.');
        }

        return hash_equals($hash, 'sha1='.hash_hmac('sha1', $data, $key));
    }
}
