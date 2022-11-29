<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Tests\Support\TestTrait;

abstract class AbstractQuoterTest extends TestCase
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::ensureColumnName()
     */
    public function testEnsureColumnName(string $columnName, string $expected): void
    {
        $db = $this->getConnection();

        $this->assertSame($expected, $db->getQuoter()->ensureColumnName($columnName));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::ensureNameQuoted()
     */
    public function testEnsureNameQuoted(string $name, string $expected): void
    {
        $db = $this->getConnection();

        $this->assertSame($expected, $db->getQuoter()->ensureNameQuoted($name));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::tableNameParts()
     */
    public function testGetTableNameParts(string $tableName, string ...$expected): void
    {
        $db = $this->getConnection();

        $this->assertSame($expected, array_reverse($db->getQuoter()->getTableNameParts($tableName)));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::columnName()
     */
    public function testQuoteColumnName(string $columnName, string $expected): void
    {
        $db = $this->getConnection();

        $this->assertSame($expected, $db->getQuoter()->quoteColumnName($columnName));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::columnName()
     */
    public function testQuoteSimpleColumnName(string $columnName, string $expected): void
    {
        $db = $this->getConnection();

        $this->assertSame($expected, $db->getQuoter()->quoteSimpleColumnName($columnName));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::simpleTableName()
     */
    public function testQuoteSimpleTableName(string $columnName, string $expected): void
    {
        $db = $this->getConnection();

        $this->assertSame($expected, $db->getQuoter()->quoteSimpleTableName($columnName));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::tableName()
     */
    public function testQuoteTableName(string $tableName, string $expected): void
    {
        $db = $this->getConnection();

        $this->assertSame($expected, $db->getQuoter()->quoteTableName($tableName));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::unquoteSimpleColumnName()
     */
    public function testUnquoteSimpleColumnName(string $columnName, string $expected): void
    {
        $db = $this->getConnection();

        $this->assertSame($expected, $db->getQuoter()->unquoteSimpleColumnName($columnName));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::unquoteSimpleTableName()
     */
    public function testUnquoteSimpleTableName(string $tableName, string $expected): void
    {
        $db = $this->getConnection();

        $this->assertSame($expected, $db->getQuoter()->unquoteSimpleTableName($tableName));
    }
}
