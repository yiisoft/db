<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Pgsql;

use Yiisoft\Db\Drivers\Connection;
use Yiisoft\Db\Helper\Dsn;
use Yiisoft\Db\Transactions\Transaction;
use Yiisoft\Db\Tests\ConnectionTest as AbstractConnectionTest;

final class ConnectionTest extends AbstractConnectionTest
{
    protected ?string $driverName = 'pgsql';

    public function testConnection(): void
    {
        $this->assertIsObject($this->getConnection(true));
    }

    public function testDsnHelper(): void
    {
        $dsn = new Dsn('pgsql', '127.0.0.1', 'yiitest', '5432');

        $connection = new Connection($this->cache, $this->logger, $this->profiler, $dsn->getDsn());

        $this->assertEquals('pgsql:host=127.0.0.1;dbname=yiitest;port=5432', $connection->getDsn());
    }

    public function testQuoteValue(): void
    {
        $connection = $this->getConnection(false);
        $this->assertEquals(123, $connection->quoteValue(123));
        $this->assertEquals("'string'", $connection->quoteValue('string'));
        $this->assertEquals("'It''s interesting'", $connection->quoteValue("It's interesting"));
    }

    public function testQuoteTableName(): void
    {
        $connection = $this->getConnection(false);
        $this->assertEquals('"table"', $connection->quoteTableName('table'));
        $this->assertEquals('"table"', $connection->quoteTableName('"table"'));
        $this->assertEquals('"schema"."table"', $connection->quoteTableName('schema.table'));
        $this->assertEquals('"schema"."table"', $connection->quoteTableName('schema."table"'));
        $this->assertEquals('"schema"."table"', $connection->quoteTableName('"schema"."table"'));
        $this->assertEquals('{{table}}', $connection->quoteTableName('{{table}}'));
        $this->assertEquals('(table)', $connection->quoteTableName('(table)'));
    }

    public function testQuoteColumnName(): void
    {
        $connection = $this->getConnection(false);
        $this->assertEquals('"column"', $connection->quoteColumnName('column'));
        $this->assertEquals('"column"', $connection->quoteColumnName('"column"'));
        $this->assertEquals('[[column]]', $connection->quoteColumnName('[[column]]'));
        $this->assertEquals('{{column}}', $connection->quoteColumnName('{{column}}'));
        $this->assertEquals('(column)', $connection->quoteColumnName('(column)'));

        $this->assertEquals('"column"', $connection->quoteSql('[[column]]'));
        $this->assertEquals('"column"', $connection->quoteSql('{{column}}'));
    }

    public function testQuoteFullColumnName(): void
    {
        $connection = $this->getConnection(false, false);
        $this->assertEquals('"table"."column"', $connection->quoteColumnName('table.column'));
        $this->assertEquals('"table"."column"', $connection->quoteColumnName('table."column"'));
        $this->assertEquals('"table"."column"', $connection->quoteColumnName('"table".column'));
        $this->assertEquals('"table"."column"', $connection->quoteColumnName('"table"."column"'));

        $this->assertEquals('[[table.column]]', $connection->quoteColumnName('[[table.column]]'));
        $this->assertEquals('{{table}}."column"', $connection->quoteColumnName('{{table}}.column'));
        $this->assertEquals('{{table}}."column"', $connection->quoteColumnName('{{table}}."column"'));
        $this->assertEquals('{{table}}.[[column]]', $connection->quoteColumnName('{{table}}.[[column]]'));
        $this->assertEquals('{{%table}}."column"', $connection->quoteColumnName('{{%table}}.column'));
        $this->assertEquals('{{%table}}."column"', $connection->quoteColumnName('{{%table}}."column"'));

        $this->assertEquals('"table"."column"', $connection->quoteSql('[[table.column]]'));
        $this->assertEquals('"table"."column"', $connection->quoteSql('{{table}}.[[column]]'));
        $this->assertEquals('"table"."column"', $connection->quoteSql('{{table}}."column"'));
        $this->assertEquals('"table"."column"', $connection->quoteSql('{{%table}}.[[column]]'));
        $this->assertEquals('"table"."column"', $connection->quoteSql('{{%table}}."column"'));
    }

    public function testTransactionIsolation(): void
    {
        $connection = $this->getConnection(true);

        $transaction = $connection->beginTransaction();
        $transaction->setIsolationLevel(Transaction::READ_UNCOMMITTED);
        $transaction->commit();

        $transaction = $connection->beginTransaction();
        $transaction->setIsolationLevel(Transaction::READ_COMMITTED);
        $transaction->commit();

        $transaction = $connection->beginTransaction();
        $transaction->setIsolationLevel(Transaction::REPEATABLE_READ);
        $transaction->commit();

        $transaction = $connection->beginTransaction();
        $transaction->setIsolationLevel(Transaction::SERIALIZABLE);
        $transaction->commit();

        $transaction = $connection->beginTransaction();
        $transaction->setIsolationLevel(Transaction::SERIALIZABLE . ' READ ONLY DEFERRABLE');
        $transaction->commit();

        $this->assertTrue(true); // No error occurred – assert passed.
    }
}
