<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Psr\Log\LoggerInterface;
use Yiisoft\Db\Profiler\ProfilerInterface;

class Profiler implements ProfilerInterface {
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function begin(string $token, array $context = []): void
    {
        $this->logger->info('begin', $context);
    }

    public function end(string $token, array $context = []): void
    {
        $this->logger->notice('end', $context);
    }
}
