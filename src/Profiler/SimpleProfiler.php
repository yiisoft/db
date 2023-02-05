<?php

declare(strict_types=1);

namespace Yiisoft\Db\Profiler;

use Psr\Log\LoggerInterface;

class SimpleProfiler implements ProfilerInterface
{
    private array $tokens = [];

    public function __construct(private LoggerInterface $logger)
    {
    }

    public function begin(string $token, array $context = []): void
    {
        $key = $this->getKey([$token, $context]);
        $this->tokens[$key] = microtime(true);
        $this->logger->info(sprintf('Begin of query "%s"', $token), $context);
    }

    private function getKey(array $params): string
    {
        return json_encode($params);
    }

    public function end(string $token, array $context = []): void
    {
        $key = $this->getKey([$token, $context]);
        if (isset($this->tokens[$key])) {
            $elapsed = microtime(true) - $this->tokens[$key];
        }

        $this->logger->info(sprintf('End of query "%s"', $token), ['elapsed' => $elapsed ?? 'undefined'] + $context);
    }
}
