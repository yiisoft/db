<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Schema;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\Expression;
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

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::tablesNameDataProvider
     *
     * @throws InvalidArgumentException
     */
    public function testCleanUpTableNames(array $tables, string $prefixDatabase, array $expected): void
    {
        $this->assertEquals(
            $expected,
            (new Quoter('"', '"'))->cleanUpTableNames($tables)
        );
    }

    public function testCleanUpTableNamesException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('To use Expression in from() method, pass it in array format with alias.');
        (new Quoter('"', '"'))->cleanUpTableNames(
            [new Expression('(SELECT id FROM user)')],
        );
    }

    public function testCleanUpTableNamesWithCastException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Use ExpressionInterface without cast to string as object of tableName');
        (new Quoter('"', '"'))->cleanUpTableNames(
            ['tableAlias' => 123],
        );
    }

    public function testGetTableNamePartsWithDifferentQuotes(): void
    {
        $quoter = new Quoter('`', '"');

        $this->assertSame(['schema', 'table'], $quoter->getTableNameParts('"schema"."table"'));
    }

    public function testQuoteSqlWithTablePrefix(): void
    {
        $quoter = new Quoter('`', '`', 'prefix_');
        $sql = 'SELECT * FROM {{%table%}}';

        $this->assertSame('SELECT * FROM `prefix_table`', $quoter->quoteSql($sql));
    }

    public function testQuoteTableNameWithQueryAlias()
    {
        $quoter = new Quoter('`', '`');
        $name = '(SELECT * FROM table) alias';

        $this->assertSame($name, $quoter->quoteTableName($name));
    }
}
