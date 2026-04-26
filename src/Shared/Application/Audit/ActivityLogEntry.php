<?php

declare(strict_types=1);

namespace App\Shared\Application\Audit;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

use function in_array;
use function is_array;
use function str_contains;
use function strtolower;

final readonly class ActivityLogEntry
{
    public string $id;
    public ?string $actorUserId;
    public string $actorType;
    public string $action;
    public ?string $entityType;
    public ?string $entityId;
    public ?string $ip;
    public ?string $userAgent;
    public ?string $requestId;
    public string $source;
    /** @var array<string,mixed>|null */
    public ?array $payload;
    public DateTimeImmutable $createdAt;

    /**
     * @param array<string,mixed>|null $payload
     */
    public function __construct(
        string $id,
        ?string $actorUserId,
        string $actorType,
        string $action,
        ?string $entityType,
        ?string $entityId,
        ?string $ip,
        ?string $userAgent,
        ?string $requestId,
        string $source,
        ?array $payload,
        DateTimeImmutable $createdAt,
    ) {
        $this->id = $id;
        $this->actorUserId = $actorUserId;
        $this->actorType = $actorType;
        $this->action = $action;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->ip = $ip;
        $this->userAgent = $userAgent;
        $this->requestId = $requestId;
        $this->source = $source;
        $this->payload = self::sanitizePayload($payload);
        $this->createdAt = $createdAt;
    }

    /**
     * @param array<string,mixed>|null $payload
     */
    public static function user(
        string $action,
        ?string $actorUserId,
        ?string $entityType = null,
        ?string $entityId = null,
        ?array $payload = null,
        ?ActorContext $context = null,
    ): self {
        return self::fromContext($action, ActorContext::ACTOR_USER, $actorUserId, $entityType, $entityId, $payload, $context);
    }

    /**
     * @param array<string,mixed>|null $payload
     */
    public static function admin(
        string $action,
        ?string $actorUserId,
        ?string $entityType = null,
        ?string $entityId = null,
        ?array $payload = null,
        ?ActorContext $context = null,
    ): self {
        return self::fromContext($action, ActorContext::ACTOR_ADMIN, $actorUserId, $entityType, $entityId, $payload, $context);
    }

    /**
     * @param array<string,mixed>|null $payload
     */
    public static function system(
        string $action,
        ?string $entityType = null,
        ?string $entityId = null,
        ?array $payload = null,
        ?ActorContext $context = null,
    ): self {
        return self::fromContext($action, ActorContext::ACTOR_SYSTEM, null, $entityType, $entityId, $payload, $context);
    }

    /**
     * @param array<string,mixed>|null $payload
     */
    public static function api(
        string $action,
        ?string $actorUserId,
        ?string $entityType = null,
        ?string $entityId = null,
        ?array $payload = null,
        ?ActorContext $context = null,
    ): self {
        return self::fromContext($action, ActorContext::ACTOR_API_TOKEN, $actorUserId, $entityType, $entityId, $payload, $context);
    }

    /**
     * @param array<string,mixed>|null $payload
     */
    private static function fromContext(
        string $action,
        string $actorType,
        ?string $actorUserId,
        ?string $entityType,
        ?string $entityId,
        ?array $payload,
        ?ActorContext $context,
    ): self {
        return new self(
            id: Uuid::uuid7()->toString(),
            actorUserId: $context?->userId ?? $actorUserId,
            actorType: $context?->actorType ?? $actorType,
            action: $action,
            entityType: $entityType,
            entityId: $entityId,
            ip: $context?->ip,
            userAgent: $context?->userAgent,
            requestId: $context?->requestId,
            source: $context?->source ?? ActorContext::SOURCE_SYSTEM,
            payload: $payload,
            createdAt: new DateTimeImmutable(),
        );
    }

    /**
     * @param array<string,mixed>|null $payload
     * @return array<string,mixed>|null
     */
    private static function sanitizePayload(?array $payload): ?array
    {
        if ($payload === null) {
            return null;
        }

        $result = [];

        foreach ($payload as $key => $value) {
            $keyLower = strtolower((string) $key);
            if (self::isSensitiveKey($keyLower)) {
                continue;
            }

            if (is_array($value)) {
                $result[$key] = self::sanitizePayload($value);
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    private static function isSensitiveKey(string $key): bool
    {
        if (str_contains($key, 'password')) {
            return true;
        }

        return in_array(
            $key,
            [
                'token',
                'api_token',
                'access_token',
                'authorization',
                'authorization_header',
                'session_id',
                'session',
                '_csrf',
                'csrf',
                'csrf_token',
            ],
            true,
        );
    }
}
