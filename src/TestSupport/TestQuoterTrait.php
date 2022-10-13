<?php

declare(strict_types=1);

namespace Yiisoft\Db\TestSupport;

trait TestQuoterTrait
{
    /**
     * @dataProvider simpleTableNamesProvider
     */
    public function testQuoteTableName(string $tableName, string $expectedTableName): void
    {
        $quoter = $this->getConnection(false)->getQuoter();

        $unQuoted = $quoter->unquoteSimpleTableName($quoter->quoteSimpleTableName($tableName));
        $this->assertEquals($expectedTableName, $unQuoted);

        $unQuoted = $quoter->unquoteSimpleTableName($quoter->quoteTableName($tableName));
        $this->assertEquals($expectedTableName, $unQuoted);
    }

    /**
     * @dataProvider simpleColumnNamesProvider
     */
    public function testQuoteSimpleColumnName(string $columnName, string $expectedQuotedColumnName, string $expectedUnQuotedColunName): void
    {
        $quoter = $this->getConnection(false)->getQuoter();

        $quoted = $quoter->quoteSimpleColumnName($columnName);
        $this->assertEquals($expectedQuotedColumnName, $quoted);

        $unQuoted = $quoter->unquoteSimpleColumnName($quoted);
        $this->assertEquals($expectedUnQuotedColunName, $unQuoted);
    }

    /**
     * @dataProvider columnNamesProvider
     */
    public function testQuoteColumnName(string $columnName, string $expectedQuotedColumnName): void
    {
        $quoter = $this->getConnection(false)->getQuoter();

        $quoted = $quoter->quoteColumnName($columnName);
        $this->assertEquals($expectedQuotedColumnName, $quoted);
    }

    /**
     * @return string[][]
     */
    public function simpleTableNamesProvider(): array
    {
        return [
            ['test', 'test', ],
        ];
    }

    /**
     * @return string[][]
     */
    public function simpleColumnNamesProvider(): array
    {
        return [
            ['*', '*', '*'],
        ];
    }

    /**
     * @return string[][]
     */
    public function columnNamesProvider(): array
    {
        return [
            ['*', '*'],
            ['table.*', '[table].*'],
            ['[table].*', '[table].*'],
            ['table.column', '[table].[column]'],
            ['[table].column', '[table].[column]'],
            ['table.[column]', '[table].[column]'],
            ['[table].[column]', '[table].[column]'],
        ];
    }
}
