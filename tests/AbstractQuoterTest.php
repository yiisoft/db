<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Tests\Provider\QuoterProvider;
use Yiisoft\Db\Tests\Support\TestTrait;

abstract class AbstractQuoterTest extends TestCase
{
    use TestTrait;

    #[DataProviderExternal(QuoterProvider::class, 'ensureColumnName')]
    public function testEnsureColumnName(string $columnName, string $expected): void
    {
        $db = $this->getConnection();

        $this->assertSame($expected, $db->getQuoter()->ensureColumnName($columnName));
    }

    #[DataProviderExternal(QuoterProvider::class, 'ensureNameQuoted')]
    public function testEnsureNameQuoted(string $name, string $expected): void
    {
        $db = $this->getConnection();

        $this->assertSame($expected, $db->getQuoter()->ensureNameQuoted($name));
    }

    #[DataProviderExternal(QuoterProvider::class, 'rawTableNames')]
    public function testGetRawTableName(string $tableName, string $expected, string $tablePrefix = ''): void
    {
        $db = $this->getConnection();

        $db->setTablePrefix($tablePrefix);

        $this->assertSame($expected, $db->getQuoter()->getRawTableName($tableName));
    }

    #[DataProviderExternal(QuoterProvider::class, 'tableNameParts')]
    public function testGetTableNameParts(string $tableName, array $expected): void
    {
        $db = $this->getConnection();

        $this->assertSame($expected, $db->getQuoter()->getTableNameParts($tableName));
    }

    #[DataProviderExternal(QuoterProvider::class, 'columnNames')]
    public function testQuoteColumnName(string $columnName, string $expected): void
    {
        $db = $this->getConnection();

        $this->assertSame($expected, $db->getQuoter()->quoteColumnName($columnName));
    }

    #[DataProviderExternal(QuoterProvider::class, 'simpleColumnNames')]
    public function testQuoteSimpleColumnName(
        string $columnName,
        string $expectedQuotedColumnName,
        string $expectedUnQuotedColumnName,
    ): void {
        $db = $this->getConnection();

        $quoter = $db->getQuoter();
        $quoted = $quoter->quoteSimpleColumnName($columnName);

        $this->assertSame($expectedQuotedColumnName, $quoted);

        $unQuoted = $quoter->unquoteSimpleColumnName($quoted);

        $this->assertSame($expectedUnQuotedColumnName, $unQuoted);
    }

    #[DataProviderExternal(QuoterProvider::class, 'simpleTableNames')]
    public function testQuoteTableName(string $tableName, string $expected): void
    {
        $db = $this->getConnection();

        $quoter = $db->getQuoter();
        $unQuoted = $quoter->unquoteSimpleTableName($quoter->quoteSimpleTableName($tableName));

        $this->assertSame($expected, $unQuoted);

        $unQuoted = $quoter->unquoteSimpleTableName($quoter->quoteTableName($tableName));

        $this->assertSame($expected, $unQuoted);
    }
}
