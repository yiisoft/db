<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Driver\Pdo;

use PDO;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Tests\Support\Stub\ExecutingCommand;
use Yiisoft\Db\Tests\Support\Stub\StubConnection;
use Yiisoft\Db\Tests\Support\Stub\StubPdoDriver;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;

/**
 * Tests for the retry / reconnect logic and bindParam re-binding in AbstractPdoCommand.
 *
 * @group db
 */
final class AbstractPdoCommandTest extends TestCase
{
    private function createConnection(): StubConnection
    {
        return new StubConnection(
            new StubPdoDriver('sqlite::memory:'),
            new SchemaCache(new MemorySimpleCache()),
        );
    }

    private function createConnectionWithTable(): StubConnection
    {
        $db = $this->createConnection();
        $db->open();
        $pdo = $db->getActivePdo();
        $pdo->exec('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');
        $pdo->exec("INSERT INTO test VALUES (1, 'Alice')");
        $pdo->exec("INSERT INTO test VALUES (2, 'Bob')");

        return $db;
    }

    private function makeCommand(StubConnection $db, int $failuresBeforeSuccess = 0): ExecutingCommand
    {
        return new ExecutingCommand($db, $failuresBeforeSuccess);
    }


    // -- bindParam: saving and re-binding after cancel() ------------------

    public function testBindParamSurvivesCancel(): void
    {
        $db = $this->createConnectionWithTable();
        $command = $this->makeCommand($db);

        $id = 1;
        $command->setSql('SELECT name FROM test WHERE id = :id');
        $command->bindParam(':id', $id);
        $command->cancel();

        $this->assertSame('Alice', $command->queryScalar());
    }

    public function testBindParamReferenceIsTracked(): void
    {
        $db = $this->createConnectionWithTable();
        $command = $this->makeCommand($db);

        $id = 1;
        $command->setSql('SELECT name FROM test WHERE id = :id');
        $command->bindParam(':id', $id);

        $command->cancel();
        $this->assertSame('Alice', $command->queryScalar());

        $id = 2;
        $command->cancel();
        $this->assertSame('Bob', $command->queryScalar());
    }

    public function testBindParamWithLengthSurvivesCancel(): void
    {
        $db = $this->createConnectionWithTable();
        $command = $this->makeCommand($db);

        $id = 1;
        $command->setSql('SELECT name FROM test WHERE id = :id');
        $command->bindParam(':id', $id, PDO::PARAM_INT, 4);
        $command->cancel();

        $this->assertSame('Alice', $command->queryScalar());
    }

    public function testResetClearsPendingBoundParams(): void
    {
        $db = $this->createConnectionWithTable();
        $command = $this->makeCommand($db);

        $id = 1;
        $command->setSql('SELECT name FROM test WHERE id = :id');
        $command->bindParam(':id', $id);

        // setSql() calls cancel() + reset(); pendingBoundParams must be cleared
        $command->setSql('SELECT 1');
        $result = $command->queryScalar();

        $this->assertSame('1', (string) $result);
    }

    // -- Default reconnect ------------------------------------------------

    public function testDefaultReconnectAttemptsOnConnectionError(): void
    {
        $db = $this->createConnectionWithTable();
        // Fail once with a "server has gone away" error, succeed on 2nd attempt.
        $command = $this->makeCommand($db, failuresBeforeSuccess: 1);
        $command->setSql('SELECT 1');

        $result = $command->queryScalar();

        $this->assertSame('1', (string) $result);
        $this->assertSame(2, $command->getExecuteCallCount());
    }

    public function testDefaultReconnectDoesNotRetryInsideTransaction(): void
    {
        $db = $this->createConnectionWithTable();
        $command = $this->makeCommand($db, failuresBeforeSuccess: 1);
        $command->setSql('SELECT 1');

        $transaction = $db->beginTransaction();

        $this->expectException(Exception::class);

        try {
            $command->queryScalar();
        } finally {
            $transaction->rollBack();
        }
    }

    // -- Custom retry handler ---------------------------------------------

    public function testCustomRetryHandlerReceivesCommandInterface(): void
    {
        $db = $this->createConnectionWithTable();
        $command = $this->makeCommand($db, failuresBeforeSuccess: 1);
        $command->setSql('SELECT 1');

        $receivedCommand = null;

        $command->setRetryHandler(
            function (Exception $e, int $attempt, CommandInterface $cmd) use (&$receivedCommand): bool {
                $receivedCommand = $cmd;

                return false;
            },
        );

        $this->expectException(Exception::class);

        try {
            $command->queryScalar();
        } finally {
            $this->assertSame($command, $receivedCommand);
        }
    }


    public function testCustomRetryHandlerRetriesAndSucceeds(): void
    {
        $db = $this->createConnectionWithTable();
        $command = $this->makeCommand($db, failuresBeforeSuccess: 2);
        $command->setSql('SELECT 1');

        $handlerCalls = 0;

        $command->setRetryHandler(
            function (Exception $e, int $attempt) use (&$handlerCalls): bool {
                ++$handlerCalls;

                return $attempt < 3;
            },
        );

        $result = $command->queryScalar();

        $this->assertSame('1', (string) $result);
        $this->assertSame(2, $handlerCalls);
    }

    public function testLegacyTwoArgRetryHandlerStillWorks(): void
    {
        $db = $this->createConnectionWithTable();
        $command = $this->makeCommand($db, failuresBeforeSuccess: 1);
        $command->setSql('SELECT 1');

        $hitHandler = false;

        // Old-style 2-arg handler — PHP silently ignores the extra CommandInterface argument.
        $command->setRetryHandler(
            static function (Exception $e, int $attempt) use (&$hitHandler): bool {
                $hitHandler = true;

                return false;
            },
        );

        $this->expectException(Exception::class);

        try {
            $command->queryScalar();
        } finally {
            $this->assertTrue($hitHandler, 'Legacy 2-arg handler should have been invoked');
        }
    }
}

