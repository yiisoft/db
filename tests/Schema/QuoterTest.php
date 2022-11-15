<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Schema;

use Yiisoft\Db\Tests\AbstractQuoterTest;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class QuoterTest extends AbstractQuoterTest
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::tableNameParts()
     */
    public function testGetTableNameParts(string $tableName, array $expected): void
    {
        $this->assertSame($expected, $this->getQuoter()->getTableNameParts($tableName));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::columnName()
     */
    public function testQuoteColumnName(string $columnName, string $expected): void
    {
        $this->assertSame($expected, $this->getQuoter()->quoteColumnName($columnName));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::simpleColumnName()
     */
    public function testQuoteSimpleColumnName(string $columnName, string $expected): void
    {
        $this->assertSame($expected, $this->getQuoter()->quoteSimpleColumnName($columnName));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::simpleTableName()
     */
    public function testQuoteSimpleTableName(string $columnName, string $expected): void
    {
        $this->assertSame($expected, $this->getQuoter()->quoteSimpleTableName($columnName));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::tableName()
     */
    public function testQuoteTableName(string $tableName, string $expected): void
    {
        $this->assertSame($expected, $this->getQuoter()->quoteTableName($tableName));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::unquoteSimpleColumnName()
     */
    public function testUnquoteSimpleColumnName(string $columnName, string $expected): void
    {
        $this->assertSame($expected, $this->getQuoter()->unquoteSimpleColumnName($columnName));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::unquoteSimpleTableName()
     */
    public function testUnquoteSimpleTableName(string $tableName, string $expected): void
    {
        $this->assertSame($expected, $this->getQuoter()->unquoteSimpleTableName($tableName));
    }
}
