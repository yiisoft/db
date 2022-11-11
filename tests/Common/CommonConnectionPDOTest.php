<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use Yiisoft\Db\Tests\AbstractConnectionPDOTest;
use Yiisoft\Db\Tests\Support\TestTrait;

use function chr;
use function mb_chr;
use function str_replace;

/**
 * @group mssql
 * @group mysql
 * @group pgsql
 * @group oracle
 * @group sqlite
 */
abstract class CommonConnectionPDOTest extends AbstractConnectionPDOTest
{
    use TestTrait;

    /**
     * Ensure database connection is reset on when a connection is cloned.
     *
     * Make sure each connection element has its own PDO instance i.e. own connection to the DB.
     * Also, transaction elements should not be shared between two connections.
     */
    public function testClone(): void
    {
        $db = $this->getConnection();

        $this->assertNull($db->getTransaction());
        $this->assertNull($db->getPDO());

        $db->open();

        $this->assertNull($db->getTransaction());
        $this->assertNotNull($db->getPDO());

        $conn2 = clone $db;

        $this->assertNull($db->getTransaction());
        $this->assertNotNull($db->getPDO());

        $this->assertNull($conn2->getTransaction());
        $this->assertNull($conn2->getPDO());

        $db->beginTransaction();

        $this->assertNotNull($db->getTransaction());
        $this->assertNotNull($db->getPDO());

        $this->assertNull($conn2->getTransaction());
        $this->assertNull($conn2->getPDO());

        $conn3 = clone $db;

        $this->assertNotNull($db->getTransaction());
        $this->assertNotNull($db->getPDO());
        $this->assertNull($conn3->getTransaction());
        $this->assertNull($conn3->getPDO());
    }

    public function testInsertEx(): void
    {
        $db = $this->getConnectionWithData();

        $result = $db
            ->createCommand()
            ->insertEx('customer', ['name' => 'testParams', 'email' => 'testParams@example.com', 'address' => '1']);

        $this->assertIsArray($result);
        $this->assertNotNull($result['id']);
    }

    public function testQuoteValue(): void
    {
        $db = $this->getConnectionWithData();

        $db->createCommand(
            <<<SQL
            DELETE FROM {{quoter}}
            SQL
        )->execute();
        $data = $this->generateQuoterEscapingValues();

        foreach ($data as $index => $value) {
            $quotedName = $db->quoteValue('testValue_' . $index);
            $quoteValue = $db->quoteValue($value);

            $db->createCommand(
                <<<SQL
                INSERT INTO {{quoter}} ([[name]], [[description]]) values ($quotedName, $quoteValue)
                SQL
            )->execute();
            $result = $db->createCommand(
                <<<SQL
                SELECT * FROM {{quoter}} WHERE [[name]]=$quotedName
                SQL
            )->queryOne();

            $this->assertSame($value, $result['description']);
        }
    }

    public function testQuoteValueEscapingValueFull()
    {
        $db = $this->getConnectionWithData();

        $template = 'aaaaa{1}aaa{1}aaaabbbbb{2}bbbb{2}bbbb';
        $db->createCommand(
            <<<SQL
            DELETE FROM {{quoter}}
            SQL
        )->execute();

        for ($symbol1 = 1; $symbol1 <= 127; $symbol1++) {
            for ($symbol2 = 1; $symbol2 <= 127; $symbol2++) {
                $quotedName = $db->quoteValue('test_' . $symbol1 . '_' . $symbol2);
                $testString = str_replace(['{1}', '{2}',], [chr($symbol1), chr($symbol2)], $template);
                $quoteValue = $db->quoteValue($testString);
                $db->createCommand(
                    <<<SQL
                    INSERT INTO {{quoter}} ([[name]], [[description]]) values ($quotedName, $quoteValue)
                    SQL
                )->execute();
                $result = $db->createCommand(
                    <<<SQL
                    SELECT * FROM {{quoter}} WHERE [[name]]=$quotedName
                    SQL
                )->queryOne();

                $this->assertSame($testString, $result['description']);
            }
        }
    }

    protected function generateQuoterEscapingValues(): array
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
