<?php

declare(strict_types=1);

namespace Yiisoft\Db\Logger;

interface DbLoggerInterface
{

    /**
     * @param string $logEvent
     * @param ContextInterface $context
     */
    public function log(string $logEvent, ContextInterface $context): void;

    /**
     * @param string $logEvent
     * @param string $level - level of log. {@see: Psr\Log\LogLevel}
     *
     * Set log level for concrete type of log
     */
    public function setLevel(string $logEvent, string $level): void;
}
