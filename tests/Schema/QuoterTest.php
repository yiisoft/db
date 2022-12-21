<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Schema;

use Yiisoft\Db\Schema\Quoter;
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

    public function testQuoteSimpleColumnNameWithStartingCharacterEndingCharacterEquals(): void
    {
        $quoter = new Quoter('`', '`');

        $this->assertSame('`column`', $quoter->quoteSimpleColumnName('column'));
    }

    public function testQuoteSimpleTableNameWithStartingCharacterEndingCharacterEquals(): void
    {
        $quoter = new Quoter('`', '`');

        $this->assertSame('`table`', $quoter->quoteSimpleTableName('table'));
    }

    public function testQuoteTableNameWithSchema(): void
    {
        $quoter = new Quoter('`', '`');

        $this->assertSame('`schema`.`table`', $quoter->quoteTableName('schema.table'));
    }

    public function testQuoteValueNotString(): void
    {
        $quoter = new Quoter('`', '`');

        $this->assertFalse($quoter->quoteValue(false));
        $this->assertTrue($quoter->quoteValue(true));
        $this->assertSame(1, $quoter->quoteValue(1));
        $this->assertSame([], $quoter->quoteValue([]));
    }

    public function testUnquoteSimpleColumnNameWithStartingCharacterEndingCharacterEquals(): void
    {
        $quoter = new Quoter('`', '`');

        $this->assertSame('column', $quoter->unquoteSimpleColumnName('`column`'));
    }
}
