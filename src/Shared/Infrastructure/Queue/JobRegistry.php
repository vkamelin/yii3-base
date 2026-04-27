<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Queue;

use App\Shared\Infrastructure\Queue\Exception\InvalidJobPayloadException;
use App\Shared\Infrastructure\Queue\Exception\MissingJobHandlerException;
use App\Shared\Infrastructure\Queue\Exception\UnknownJobTypeException;
use Psr\Container\ContainerInterface;

use function is_array;
use function is_string;
use function sprintf;

final class JobRegistry
{
    /** @var array<string,JobTypeDefinition> */
    private array $definitions = [];

    /**
     * @param array<string,array{job:class-string<JobInterface>,handler:class-string<JobHandlerInterface>}> $definitions
     */
    public function __construct(array $definitions = [])
    {
        foreach ($definitions as $type => $definition) {
            /** @var array{job:class-string<JobInterface>,handler:class-string<JobHandlerInterface>} $definition */
            $this->register($type, $definition['job'], $definition['handler']);
        }
    }

    /**
     * @param class-string<JobInterface> $jobClass
     * @param class-string<JobHandlerInterface> $handlerClass
     */
    public function register(string $type, string $jobClass, string $handlerClass): void
    {
        if ($type === '') {
            throw new InvalidJobPayloadException('Queue job type must not be empty.');
        }

        if (!is_subclass_of($jobClass, JobInterface::class)) {
            throw new InvalidJobPayloadException(sprintf('Queue job class "%s" must implement %s.', $jobClass, JobInterface::class));
        }

        if (!is_subclass_of($handlerClass, JobHandlerInterface::class)) {
            throw new InvalidJobPayloadException(sprintf('Queue handler class "%s" must implement %s.', $handlerClass, JobHandlerInterface::class));
        }

        $this->definitions[$type] = new JobTypeDefinition($type, $jobClass, $handlerClass);
    }

    /**
     * @param array<string,mixed> $envelope
     */
    public function createFromEnvelope(array $envelope): JobInterface
    {
        $type = $envelope['type'] ?? null;
        $payload = $envelope['payload'] ?? null;

        if (!is_string($type) || $type === '' || !is_array($payload)) {
            throw new InvalidJobPayloadException('Queue payload envelope is invalid.');
        }

        return $this->create($type, $payload);
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function create(string $type, array $payload): JobInterface
    {
        $definition = $this->definitions[$type] ?? null;
        if ($definition === null) {
            throw new UnknownJobTypeException(sprintf('Queue job type "%s" is not registered.', $type));
        }

        return $definition->jobClass::fromPayload($payload);
    }

    public function resolveHandler(JobInterface $job, ContainerInterface $container): JobHandlerInterface
    {
        $definition = $this->definitions[$job->type()] ?? null;
        if ($definition === null) {
            throw new UnknownJobTypeException(sprintf('Queue job type "%s" is not registered.', $job->type()));
        }

        try {
            $handler = $container->get($definition->handlerClass);
        } catch (\Throwable $e) {
            throw new MissingJobHandlerException(
                sprintf('Queue handler "%s" cannot be resolved for job type "%s".', $definition->handlerClass, $job->type()),
                0,
                $e,
            );
        }

        if (!$handler instanceof JobHandlerInterface) {
            throw new MissingJobHandlerException(
                sprintf('Resolved queue handler "%s" for job type "%s" is invalid.', $definition->handlerClass, $job->type()),
            );
        }

        return $handler;
    }
}
