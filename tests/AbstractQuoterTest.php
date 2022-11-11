<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Schema\Quoter;

abstract class AbstractQuoterTest extends TestCase
{
    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::ensureColumnName()
     */
    public function testsEnsureColumnName(string $columnName, string $expected): void
    {
        $quoter = new Quoter('`', '`', '');

        $this->assertSame($expected, $quoter->ensureColumnName($columnName));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::ensureNameQuoted()
     */
    public function testsEnsureNameQuoted(string $name, string $expected): void
    {
        $quoter = new Quoter('`', '`', '');

        $this->assertSame($expected, $quoter->ensureNameQuoted($name));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::tableNameParts()
     */
    public function testGetTableNameParts(string $tableName, array $expected): void
    {
        $quoter = new Quoter('`', '`', '');

        $this->assertSame($expected, $quoter->getTableNameParts($tableName));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::columnName()
     */
    public function testQuoteColumnName(string $columnName, string $expected): void
    {
        $quoter = new Quoter('`', '`', '');

        $this->assertSame($expected, $quoter->quoteColumnName($columnName));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::simpleColumnName()
     */
    public function testQuoteSimpleColumnName(string $columnName, string $expected): void
    {
        $quoter = new Quoter('`', '`', '');

        $this->assertSame($expected, $quoter->quoteSimpleColumnName($columnName));

        $quoter = new Quoter(['`', '`'], ['`', '`']);

        $this->assertSame($expected, $quoter->quoteSimpleColumnName($columnName));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::simpleTableName()
     */
    public function testQuoteSimpleTableName(string $columnName, string $expected): void
    {
        $quoter = new Quoter('`', '`', '');

        $this->assertSame($expected, $quoter->quoteSimpleTableName($columnName));

        $quoter = new Quoter(['`', '`'], ['`', '`']);

        $this->assertSame($expected, $quoter->quoteSimpleTableName($columnName));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::tableName()
     */
    public function testQuoteTableName(string $tableName, string $expected): void
    {
        $quoter = new Quoter('`', '`', '');

        $this->assertSame($expected, $quoter->quoteTableName($tableName));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::unquoteSimpleColumnName()
     */
    public function testUnquoteSimpleColumnName(string $columnName, string $expected): void
    {
        $quoter = new Quoter('`', '`', '');

        $this->assertSame($expected, $quoter->unquoteSimpleColumnName($columnName));

        $quoter = new Quoter(['`', '`'], ['`', '`']);

        $this->assertSame($expected, $quoter->unquoteSimpleColumnName($columnName));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::unquoteSimpleTableName()
     */
    public function testUnquoteSimpleTableName(string $tableName, string $expected): void
    {
        $quoter = new Quoter('`', '`', '');

        $this->assertSame($expected, $quoter->unquoteSimpleTableName($tableName));

        $quoter = new Quoter(['`', '`'], ['`', '`']);

        $this->assertSame($expected, $quoter->unquoteSimpleTableName($tableName));
    }
}
