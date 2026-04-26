<?php

declare(strict_types=1);

namespace App\Shared\Application\Audit;

use InvalidArgumentException;

use function in_array;

final readonly class ActorContext
{
    public const ACTOR_USER = 'user';
    public const ACTOR_ADMIN = 'admin';
    public const ACTOR_SYSTEM = 'system';
    public const ACTOR_GUEST = 'guest';
    public const ACTOR_API_TOKEN = 'api_token';

    public const SOURCE_WEB = 'web';
    public const SOURCE_API = 'api';
    public const SOURCE_CONSOLE = 'console';
    public const SOURCE_SYSTEM = 'system';
    public const SOURCE_QUEUE = 'queue';

    public function __construct(
        public ?string $userId,
        public string $actorType,
        public string $source,
        public ?string $requestId = null,
        public ?string $ip = null,
        public ?string $userAgent = null,
    ) {
        if (!in_array($this->actorType, self::actorTypes(), true)) {
            throw new InvalidArgumentException('Unsupported actor type.');
        }

        if (!in_array($this->source, self::sources(), true)) {
            throw new InvalidArgumentException('Unsupported source type.');
        }
    }

    public static function user(
        ?string $userId,
        string $source,
        ?string $requestId = null,
        ?string $ip = null,
        ?string $userAgent = null,
    ): self {
        return new self($userId, self::ACTOR_USER, $source, $requestId, $ip, $userAgent);
    }

    public static function admin(
        ?string $userId,
        string $source,
        ?string $requestId = null,
        ?string $ip = null,
        ?string $userAgent = null,
    ): self {
        return new self($userId, self::ACTOR_ADMIN, $source, $requestId, $ip, $userAgent);
    }

    public static function system(string $source = self::SOURCE_SYSTEM): self
    {
        return new self(null, self::ACTOR_SYSTEM, $source);
    }

    public static function guest(
        string $source,
        ?string $requestId = null,
        ?string $ip = null,
        ?string $userAgent = null,
    ): self {
        return new self(null, self::ACTOR_GUEST, $source, $requestId, $ip, $userAgent);
    }

    /**
     * @return list<string>
     */
    public static function actorTypes(): array
    {
        return [
            self::ACTOR_USER,
            self::ACTOR_ADMIN,
            self::ACTOR_SYSTEM,
            self::ACTOR_GUEST,
            self::ACTOR_API_TOKEN,
        ];
    }

    /**
     * @return list<string>
     */
    public static function sources(): array
    {
        return [
            self::SOURCE_WEB,
            self::SOURCE_API,
            self::SOURCE_CONSOLE,
            self::SOURCE_SYSTEM,
            self::SOURCE_QUEUE,
        ];
    }
}
