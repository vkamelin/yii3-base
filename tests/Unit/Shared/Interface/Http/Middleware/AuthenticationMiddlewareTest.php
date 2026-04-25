<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Interface\Http\Middleware;

use App\Auth\Application\Handler\GetAuthenticatedUserHandler;
use App\Auth\Application\Session\AuthSessionInterface;
use App\Auth\Domain\Entity\AuthToken;
use App\Auth\Domain\Repository\AuthTokenRepositoryInterface;
use App\Auth\Domain\ValueObject\TokenHash;
use App\Auth\Interface\Api\BearerTokenExtractor;
use App\Auth\Interface\Web\Response\RedirectResponseFactory;
use App\Shared\Interface\Http\ApiErrorResponder;
use App\Shared\Interface\Http\Middleware\AuthenticationMiddleware;
use App\Shared\Interface\Http\RequestAttributes;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\UserId;
use Codeception\Test\Unit;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\StreamFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function PHPUnit\Framework\assertSame;

final class AuthenticationMiddlewareTest extends Unit
{
    public function testApiWithoutBearerTokenReturns401(): void
    {
        $responseFactory = new ResponseFactory();
        $middleware = new AuthenticationMiddleware(
            authSession: new class implements AuthSessionInterface {
                public function login(string $userId): void {}

                public function logout(): void {}

                public function userId(): ?string
                {
                    return null;
                }
            },
            tokenExtractor: new BearerTokenExtractor(),
            getAuthenticatedUserHandler: $this->createNoopAuthHandler(),
            apiErrorResponder: new ApiErrorResponder($responseFactory, new StreamFactory()),
            redirectResponseFactory: new RedirectResponseFactory($responseFactory),
        );

        $request = (new ServerRequest(uri: '/api/v1/auth/me'))
            ->withAttribute(RequestAttributes::REQUEST_ID, 'req-401');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new \HttpSoft\Message\Response(200);
            }
        };

        $response = $middleware->process($request, $handler);

        assertSame(401, $response->getStatusCode());
    }

    private function createNoopAuthHandler(): GetAuthenticatedUserHandler
    {
        $tokens = new class implements AuthTokenRepositoryInterface {
            public function save(AuthToken $token): void {}

            public function findByHash(TokenHash $hash): ?AuthToken
            {
                return null;
            }

            public function revokeByHash(TokenHash $hash): void {}

            public function revokeAllForUser(UserId $userId): void {}
        };

        $users = new class implements UserRepositoryInterface {
            public function save(\App\User\Domain\Entity\User $user): void {}

            public function findById(UserId $id): ?\App\User\Domain\Entity\User
            {
                return null;
            }

            public function findByEmail(Email $email): ?\App\User\Domain\Entity\User
            {
                return null;
            }

            public function existsByEmail(Email $email): bool
            {
                return false;
            }
        };

        return new GetAuthenticatedUserHandler($tokens, $users);
    }
}
