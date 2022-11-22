<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Schema;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class QuoterTest extends TestCase
{
    use TestTrait;

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
