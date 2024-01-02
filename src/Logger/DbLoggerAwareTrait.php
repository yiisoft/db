<?php

declare(strict_types=1);

namespace Yiisoft\Db\Logger;

use Psr\Log\LoggerInterface as PsrLoggerInterface;

/**
 * Basic Implementation of LoggerAwareInterface.
 */
trait DbLoggerAwareTrait
{
    /**
     * The logger instance.
     *
     * @var DbLoggerInterface|null
     */
    protected ?DbLoggerInterface $logger = null;

    /**
     * Sets a logger.
     *
     * @param DbLoggerInterface|PsrLoggerInterface $logger
     */
    public function setLogger(DbLoggerInterface|PsrLoggerInterface $logger): void
    {
        if ($logger instanceof PsrLoggerInterface) {
            $logger = new DbLogger($logger);
        }
        $this->logger = $logger;
    }
}
