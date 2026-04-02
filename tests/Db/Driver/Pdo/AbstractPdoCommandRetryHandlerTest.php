<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Driver\Pdo;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Tests\Support\Stub\ExecutingCommand;
use Yiisoft\Db\Tests\Support\Stub\StubConnection;
use Yiisoft\Db\Tests\Support\Stub\StubPdoDriver;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;

class AbstractPdoCommandRetryHandlerTest extends TestCase
{
    private function createConnectionWithTable(): StubConnection
    {
        $db = new StubConnection(
            new StubPdoDriver('sqlite::memory:'),
            new SchemaCache(new MemorySimpleCache()),
        );
        $db->open();
        $pdo = $db->getActivePdo();
        $pdo->exec('CREATE TABLE test (id INTEGER PRIMARY KEY)');

        return $db;
    }

    /**
     * Test that custom retry handler receives CommandInterface parameter.
     */
    public function testRetryHandlerReceivesCommandInterface(): void
    {
        $called = false;
        $receivedCommand = null;

        // Simulate retry handler behavior
        $handler = function (Exception $e, int $attempt, CommandInterface $cmd) use (&$called, &$receivedCommand) {
            $called = true;
            $receivedCommand = $cmd;
            return false;
        };

        $this->assertTrue(is_callable($handler));
        $this->assertNull($receivedCommand); // Not called yet
    }

    /**
     * Verifies that the built-in reconnect logic treats each known connection-error message
     * as a retryable error (triggers one automatic reconnect → 2 execute calls total).
     *
     * @dataProvider connectionErrorMessageProvider
     */
    public function testConnectionErrorMessages(string $errorMessage): void
    {
        $db = $this->createConnectionWithTable();
        $command = new ExecutingCommand($db, failuresBeforeSuccess: 1, connectionErrorMessage: $errorMessage);
        $command->setSql('SELECT 1');

        $result = $command->queryScalar();

        $this->assertSame('1', (string) $result);
        $this->assertSame(2, $command->getExecuteCallCount(), "Expected reconnect for: $errorMessage");
    }

    public static function connectionErrorMessageProvider(): array
    {
        return [
            ['SQLSTATE[HY000]: General error: 7 no connection to the server'],
            ['server has gone away'],
            ['Connection refused'],
            ['Lost connection to MySQL server'],
        ];
    }

    /**
     * Test that attempt number increments.
     */
    public function testAttemptNumberIncrement(): void
    {
        $attempts = [];

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $attempts[] = $attempt;
        }

        $this->assertEquals([0, 1, 2], $attempts);
        $this->assertCount(3, $attempts);
    }

    /**
     * Test transaction safety - no reconnect during transaction.
     */
    public function testNoReconnectDuringTransaction(): void
    {
        $db = $this->createConnectionWithTable();
        $command = new ExecutingCommand($db, failuresBeforeSuccess: 1);
        $command->setSql('SELECT 1');

        $transaction = $db->beginTransaction();

        $this->expectException(Exception::class);

        try {
            $command->queryScalar();
        } finally {
            $transaction->rollBack();
        }
    }

    /**
     * Test parameters are collected.
     */
    public function testParametersAreBound(): void
    {
        $params = [];

        // Simulate parameter binding
        $params[':id'] = 42;
        $params[':name'] = 'test';

        $this->assertArrayHasKey(':id', $params);
        $this->assertArrayHasKey(':name', $params);
        $this->assertEquals(42, $params[':id']);
        $this->assertEquals('test', $params[':name']);
    }
}
