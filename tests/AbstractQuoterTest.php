<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;

abstract class AbstractQuoterTest extends TestCase
{
    public function testQuoterEscapingValue()
    {
        $db = $this->getConnection();

        $quoter = $db->getQuoter();

        $db->createCommand('delete from {{quoter}}')->execute();
        $data = $this->generateQuoterEscapingValues();

        foreach ($data as $index => $value) {
            $quotedName = $quoter->quoteValue('testValue_' . $index);
            $quoteValue = $quoter->quoteValue($value);

            $db->createCommand(
                'insert into {{quoter}}([[name]], [[description]]) values(' . $quotedName . ', ' . $quoteValue . ')'
            )->execute();
            $result = $db->createCommand('select * from {{quoter}} where [[name]]=' . $quotedName)->queryOne();

            $this->assertSame($value, $result['description']);
        }
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::columnNames
     */
    public function testQuoteColumnName(string $columnName, string $expectedQuotedColumnName): void
    {
        $db = $this->getConnection();

        $quoter = $db->getQuoter();
        $quoted = $quoter->quoteColumnName($columnName);

        $this->assertSame($expectedQuotedColumnName, $quoted);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::simpleColumnNames
     */
    public function testQuoteSimpleColumnName(
        string $columnName,
        string $expectedQuotedColumnName,
        string $expectedUnQuotedColunName
    ): void {
        $db = $this->getConnection();

        $quoter = $db->getQuoter();
        $quoted = $quoter->quoteSimpleColumnName($columnName);

        $this->assertSame($expectedQuotedColumnName, $quoted);

        $unQuoted = $quoter->unquoteSimpleColumnName($quoted);

        $this->assertSame($expectedUnQuotedColunName, $unQuoted);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::simpleTableNames()
     */
    public function testQuoteSimpleTableName(string $tableName, string $expectedTableName): void
    {
        $db = $this->getConnection();

        $quoter = $db->getQuoter();
        $unQuoted = $quoter->unquoteSimpleTableName($quoter->quoteSimpleTableName($tableName));

        $this->assertSame($expectedTableName, $unQuoted);

        $unQuoted = $quoter->unquoteSimpleTableName($quoter->quoteTableName($tableName));

        $this->assertSame($expectedTableName, $unQuoted);
    }

    private function generateQuoterEscapingValues()
    {
        $result = [];
        $stringLength = 16;

        for ($i = 32; $i < 128 - $stringLength; $i += $stringLength) {
            $str = '';
            for ($symbol = $i; $symbol < $i + $stringLength; $symbol++) {
                $str .= mb_chr($symbol, 'UTF-8');
            }
            $result[] = $str;

            $str = '';
            for ($symbol = $i; $symbol < $i + $stringLength; $symbol++) {
                $str .= mb_chr($symbol, 'UTF-8') . mb_chr($symbol, 'UTF-8');
            }
            $result[] = $str;
        }

        return $result;
    }
}
