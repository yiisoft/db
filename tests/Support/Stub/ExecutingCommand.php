<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Yiisoft\Db\Driver\Pdo\AbstractPdoCommand;

/**
 * A minimal concrete PDO command that uses the real {@see AbstractPdoCommand::internalExecute()} implementation.
 *
 * Unlike {@see Command}, which throws {@see \Yiisoft\Db\Exception\NotSupportedException}, this stub is designed
 * for unit tests that need actual SQL execution via SQLite in-memory database.
 *
 * An optional `$failuresBeforeSuccess` constructor argument makes the first N `pdoStatementExecute()` calls
 * throw a simulated connection PDOException, then succeed — useful for testing the reconnect/retry logic.
 */
final class ExecutingCommand extends AbstractPdoCommand
{
    private int $executeCallCount = 0;

    public function __construct(
        StubConnection $db,
        private readonly int $failuresBeforeSuccess = 0,
        private readonly string $connectionErrorMessage = 'server has gone away',
    ) {
        parent::__construct($db);
    }

    public function showDatabases(): array
    {
        return [];
    }

    protected function pdoStatementExecute(): void
    {
        if ($this->executeCallCount < $this->failuresBeforeSuccess) {
            ++$this->executeCallCount;

            throw new \PDOException($this->connectionErrorMessage);
        }

        ++$this->executeCallCount;
        parent::pdoStatementExecute();
    }

    public function getExecuteCallCount(): int
    {
        return $this->executeCallCount;
    }
}

