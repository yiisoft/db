<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\Pdo;

use Closure;
use Throwable;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Exception\ConnectionException;
use Yiisoft\Db\Exception\Exception;

/**
 * Default retry handler that implements automatic connection recovery on the first execution failure.
 *
 * Detects {@see ConnectionException} errors and attempts to reconnect and re-prepare the statement before retrying.
 * No retry is performed when:
 * - it is not the first attempt,
 * - the error is not a {@see ConnectionException},
 * - a transaction is active (reconnecting would silently roll it back),
 * - the reconnection itself fails.
 *
 * Set as the default {@see AbstractPdoCommand::$retryHandler}.
 * Replace it via {@see CommandInterface::setRetryHandler()} to customize retry behavior.
 *
 * @psalm-type RetryHandlerClosure = Closure(Exception, int, CommandInterface): bool
 */
final class ConnectionRecoveryHandler
{
    public function __construct(private readonly PdoConnectionInterface $db) {}

    /**
     * @psalm-return RetryHandlerClosure
     */
    public function asClosure(): Closure
    {
        return $this->__invoke(...);
    }

    public function __invoke(Exception $e, int $attempt, CommandInterface $command): bool
    {
        // Only attempt recovery on the first failure.
        if ($attempt !== 0 || !$e instanceof ConnectionException) {
            return false;
        }

        // Reconnecting during an active transaction would silently roll it back.
        if ($this->db->getTransaction() !== null) {
            return false;
        }

        // Try to renew the connection.
        try {
            $this->db->close();
            $this->db->open();
            $command->cancel(); // resets the PDOStatement so prepare() creates a fresh one
        } catch (Throwable) {
            return false;
        }

        // Re-prepare the statement against the new connection, restoring all parameter bindings.
        $command->prepare();

        return true;
    }
}
