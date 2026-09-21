<?php

namespace App\Tests\Service;

use App\Event\EventDispatcher;
use App\Service\GitHubRequestHandler;
use App\Service\RepositoryProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class GitHubRequestHandlerTest extends TestCase
{
    #[DataProvider('provideRepositoriesWithoutSecret')]
    public function testRepositoryWithoutSecretIsRejected(array $repositoryData)
    {
        $handler = $this->createHandler(['carsonbot-playground/symfony' => $repositoryData]);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('No webhook secret configured for repository "carsonbot-playground/symfony".');

        $handler->handle($this->createRequest($this->createBody()));
    }

    public static function provideRepositoriesWithoutSecret(): iterable
    {
        yield 'no secret key' => [[]];
        yield 'empty secret' => [['secret' => '']];
        yield 'blank secret' => [['secret' => '   ']];
    }

    public function testSignedRequestIsAccepted()
    {
        $handler = $this->createHandler(['carsonbot-playground/symfony' => ['secret' => 'a_secret']]);
        $body = $this->createBody();

        $responseData = $handler->handle($this->createRequest($body, 'sha1='.hash_hmac('sha1', $body, 'a_secret')));

        $this->assertSame(['unsupported_action' => 'issues'], $responseData);
    }

    /**
     * @param array<string, array{secret?: string}> $repositories
     */
    private function createHandler(array $repositories): GitHubRequestHandler
    {
        return new GitHubRequestHandler(new EventDispatcher(), new RepositoryProvider($repositories), new NullLogger());
    }

    private function createRequest(string $body, ?string $signature = null): Request
    {
        $server = ['HTTP_X-Github-Event' => 'issues'];
        if (null !== $signature) {
            $server['HTTP_X-Hub-Signature'] = $signature;
        }

        return Request::create('/webhooks/github', 'POST', [], [], [], $server, $body);
    }

    private function createBody(): string
    {
        return json_encode(['repository' => ['full_name' => 'carsonbot-playground/symfony'], 'action' => 'opened']);
    }
}
