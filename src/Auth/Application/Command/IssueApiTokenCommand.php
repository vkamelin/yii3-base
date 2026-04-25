<?php

declare(strict_types=1);

namespace App\Auth\Application\Command;

use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final readonly class IssueApiTokenCommand
{
    /**
     * @param list<string>|null $abilities
     */
    public function __construct(
        public string $userId,
        public ?string $name = null,
        public ?array $abilities = null,
        public ?DateTimeImmutable $expiresAt = null,
    ) {
        if (!Uuid::isValid($this->userId)) {
            throw new InvalidArgumentException('Invalid user ID.');
        }

        if ($this->abilities === null) {
            return;
        }

        foreach ($this->abilities as $ability) {
            if ($ability === '') {
                throw new InvalidArgumentException('Abilities must be a list of non-empty strings.');
            }
        }
    }
}
