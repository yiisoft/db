<?php

declare(strict_types=1);

namespace Yiisoft\Db\Logger;

use Psr\Log\LoggerInterface as PsrLoggerInterface;

/**
 * Describes a logger-aware instance.
 */
interface DbLoggerAwareInterface
{
    /**
     * Sets a logger instance on the object.
     *
     * @param DbLoggerInterface|PsrLoggerInterface $logger
     */
    public function setLogger(DbLoggerInterface|PsrLoggerInterface $logger): void;
}
