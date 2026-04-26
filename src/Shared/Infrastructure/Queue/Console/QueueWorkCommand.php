<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Queue\Console;

use App\Shared\Application\Tracing\TraceContextProviderInterface;
use App\Shared\Infrastructure\Queue\MySqlQueue;
use App\Shared\Infrastructure\Queue\RedisQueue;
use App\Shared\Infrastructure\Queue\Worker;
use App\Shared\Infrastructure\Queue\WorkerOptions;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(
    name: 'queue:work',
    description: 'Run queue worker loop',
)]
final class QueueWorkCommand extends Command
{
    public function __construct(
        private readonly Worker $worker,
        private readonly MySqlQueue $mySqlQueue,
        private readonly RedisQueue $redisQueue,
        private readonly LoggerInterface $logger,
        private readonly TraceContextProviderInterface $traceContextProvider,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('sleep', null, InputOption::VALUE_REQUIRED, 'Sleep seconds when queue is empty.', '3')
            ->addOption('max-jobs', null, InputOption::VALUE_REQUIRED, 'Stop after N processed jobs, 0 means unlimited.', '0')
            ->addOption('max-time', null, InputOption::VALUE_REQUIRED, 'Stop after N seconds, 0 means unlimited.', '0')
            ->addOption('memory', null, InputOption::VALUE_REQUIRED, 'Stop if memory usage exceeds N megabytes.', '128')
            ->addOption('queue', null, InputOption::VALUE_REQUIRED, 'Queue backend: mysql|redis.', 'mysql');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sleep = $this->intOption($input, 'sleep');
        $maxJobs = $this->intOption($input, 'max-jobs');
        $maxTime = $this->intOption($input, 'max-time');
        $memory = $this->intOption($input, 'memory');
        $queueName = (string) $input->getOption('queue');

        if ($sleep < 0 || $maxJobs < 0 || $maxTime < 0 || $memory <= 0) {
            $output->writeln('<error>Invalid options: --sleep>=0, --max-jobs>=0, --max-time>=0, --memory>0.</error>');
            return ExitCode::USAGE;
        }

        $queue = match ($queueName) {
            'mysql' => $this->mySqlQueue,
            'redis' => $this->redisQueue,
            default => null,
        };

        if ($queue === null) {
            $output->writeln('<error>Unknown queue backend. Supported: mysql, redis.</error>');
            return ExitCode::DATAERR;
        }

        $traceContext = $this->traceContextProvider->get();
        $this->logger->info('console.queue.work.started', [
            'source' => 'console',
            'request_id' => $traceContext->requestId(),
            'correlation_id' => $traceContext->correlationId(),
            'queue' => $queueName,
            'sleep' => $sleep,
            'max_jobs' => $maxJobs,
            'max_time' => $maxTime,
            'memory' => $memory,
        ]);

        try {
            $result = $this->worker->run(
                $queue,
                new WorkerOptions(
                    sleepSeconds: $sleep,
                    maxJobs: $maxJobs,
                    maxTimeSeconds: $maxTime,
                    memoryLimitMb: $memory,
                    queueName: $queueName,
                ),
            );
        } catch (\Throwable $e) {
            $this->logger->error('console.queue.work.failed', [
                'source' => 'console',
                'request_id' => $traceContext->requestId(),
                'correlation_id' => $traceContext->correlationId(),
                'queue' => $queueName,
                'error' => $e->getMessage(),
            ]);
            $output->writeln(sprintf('<error>Worker crashed: %s</error>', $e->getMessage()));
            return ExitCode::SOFTWARE;
        }

        $this->logger->info('console.queue.work.completed', [
            'source' => 'console',
            'request_id' => $traceContext->requestId(),
            'correlation_id' => $traceContext->correlationId(),
            'queue' => $queueName,
            'processed' => $result->processedJobs,
            'failed' => $result->failedJobs,
            'stop_reason' => $result->stopReason,
        ]);

        $output->writeln(
            sprintf(
                'Worker stopped (%s). processed=%d failed=%d',
                $result->stopReason,
                $result->processedJobs,
                $result->failedJobs,
            ),
        );

        return ExitCode::OK;
    }

    private function intOption(InputInterface $input, string $name): int
    {
        return (int) $input->getOption($name);
    }
}
