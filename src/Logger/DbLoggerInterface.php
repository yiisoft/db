<?php

declare(strict_types=1);

namespace Yiisoft\Db\Logger;

interface DbLoggerInterface
{
    public function log(string $logEvent, ContextInterface $context): void;

    /**
     * @param string $level - level of log. {@see: Psr\Log\LogLevel}
     *
     * Set log level for concrete type of log
     */
    public function setLevel(string $logEvent, string $level): void;
}
