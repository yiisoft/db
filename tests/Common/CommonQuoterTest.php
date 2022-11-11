<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use Yiisoft\Db\Tests\AbstractQuoterTest;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group mssql
 * @group mysql
 * @group pgsql
 * @group oracle
 * @group sqlite
 */
abstract class CommonQuoterTest extends AbstractQuoterTest
{
    use TestTrait;

    public function testQuoteColumnNameWithDbGetQuoter(string $columnName, string $expected): void
    {
        $db = $this->getConnection();

        $quoter = $db->getQuoter();
        $quoted = $quoter->quoteColumnName($columnName);

        $this->assertSame($expected, $quoted);
    }

    public function testQuoteSimpleColumnNameWithDbGetQuoter(string $columnName, string $expected): void
    {
        $db = $this->getConnection();

        $quoter = $db->getQuoter();
        $quoted = $quoter->quoteSimpleColumnName($columnName);

        $this->assertSame($expected, $quoted);
    }

    public function testQuoteSimpleTableNameWithDbGetQuoter(string $tableName, string $expected): void
    {
        $db = $this->getConnection();

        $quoter = $db->getQuoter();
        $quoted = $quoter->quoteSimpleTableName($tableName);

        $this->assertSame($expected, $quoted);
    }

    public function testUnquoteSimpleColumnNameWithDbGetQuoter(string $tableName, string $expected): void
    {
        $db = $this->getConnection();

        $quoter = $db->getQuoter();
        $quoted = $quoter->unquoteSimpleColumnName($tableName);

        $this->assertSame($expected, $quoted);
    }

    public function testUnquoteSimpleTableNameWithDbGetQuoter(string $tableName, string $expected): void
    {
        $db = $this->getConnection();

        $quoter = $db->getQuoter();
        $unquoted = $quoter->unquoteSimpleTableName($tableName);

        $this->assertSame($expected, $unquoted);
    }
}
