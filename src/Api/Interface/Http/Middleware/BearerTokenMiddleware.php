<?php

declare(strict_types=1);

namespace App\Api\Interface\Http\Middleware;

use App\Api\Interface\Http\Response\ApiErrorResponseFactory;
use App\Auth\Application\Command\GetAuthenticatedUserCommand;
use App\Auth\Application\Handler\GetAuthenticatedUserHandler;
use App\Auth\Interface\Api\BearerTokenExtractor;
use App\Shared\Application\Audit\ActivityLogEntry;
use App\Shared\Application\Audit\ActivityLoggerInterface;
use App\Shared\Application\Audit\ActorContext;
use App\Shared\Application\Audit\Action\ApiAuditAction;
use App\Shared\Application\Tracing\TraceContextProviderInterface;
use App\Shared\Application\Exception\AccessDeniedException;
use App\Shared\Application\Exception\InvalidCredentialsException;
use App\Shared\Application\Exception\ValidationException;
use App\Shared\Interface\Http\RequestAttributes;
use App\Shared\Infrastructure\Audit\RequestAuditContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

use function in_array;
use function str_starts_with;

final readonly class BearerTokenMiddleware implements MiddlewareInterface
{
    /**
     * @param list<string> $apiPrefixes
     * @param list<string> $publicPaths
     */
    public function __construct(
        private BearerTokenExtractor $tokenExtractor,
        private GetAuthenticatedUserHandler $getAuthenticatedUserHandler,
        private ApiErrorResponseFactory $errorResponseFactory,
        private ActivityLoggerInterface $activityLogger,
        private RequestAuditContext $auditContext,
        private TraceContextProviderInterface $traceContextProvider,
        private LoggerInterface $logger,
        private array $apiPrefixes = ['/api', '/api/'],
        private array $publicPaths = ['/api/v1/auth/login'],
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isApiRequest($request)) {
            return $handler->handle($request);
        }

        if (in_array($request->getUri()->getPath(), $this->publicPaths, true)) {
            return $handler->handle($request);
        }

        $token = $this->tokenExtractor->extract($request);
        if ($token === null) {
            $this->logAuthFailed($request, 'missing_token');
            return $this->errorResponseFactory->unauthenticated($request, 'Missing bearer token.');
        }

        try {
            $authResult = $this->getAuthenticatedUserHandler->handle(new GetAuthenticatedUserCommand($token));
        } catch (InvalidCredentialsException|ValidationException|AccessDeniedException) {
            $this->logAuthFailed($request, 'invalid_token');
            return $this->errorResponseFactory->unauthenticated($request, 'Invalid bearer token.');
        }

        $request = $request
            ->withAttribute(RequestAttributes::USER_ID, $authResult->userId)
            ->withAttribute(RequestAttributes::AUTH_CHANNEL, 'api')
            ->withAttribute(RequestAttributes::API_TOKEN, $token)
            ->withAttribute(RequestAttributes::AUTH_RESULT, $authResult);

        return $handler->handle($request);
    }

    private function isApiRequest(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();
        foreach ($this->apiPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function logAuthFailed(ServerRequestInterface $request, string $reason): void
    {
        $traceContext = $this->traceContextProvider->get();

        $context = $this->auditContext->fromRequest(
            request: $request,
            defaultSource: ActorContext::SOURCE_API,
            defaultActorType: ActorContext::ACTOR_GUEST,
        );

        $this->activityLogger->log(ActivityLogEntry::api(
            action: ApiAuditAction::TOKEN_AUTH_FAILED,
            actorUserId: $context->userId,
            entityType: 'api_request',
            payload: [
                'reason' => $reason,
                'path' => $request->getUri()->getPath(),
                'method' => $request->getMethod(),
            ],
            context: $context,
        ));

        $this->logger->warning('api.auth.failed', [
            'type' => 'security',
            'request_id' => $traceContext->requestId(),
            'correlation_id' => $traceContext->correlationId(),
            'reason' => $reason,
            'path' => $request->getUri()->getPath(),
            'method' => $request->getMethod(),
            'source' => ActorContext::SOURCE_API,
        ]);
    }
}
