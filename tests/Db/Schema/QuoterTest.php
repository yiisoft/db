<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Schema;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\Quoter;

/**
 * @group db
 */
final class QuoterTest extends TestCase
{
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
            (new Quoter('"', '"'))->cleanUpTableNames($tables),
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

        $this->assertSame(['schemaName' => 'schema', 'name' => 'table'], $quoter->getTableNameParts('"schema"."table"'));
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

    public static function dataQuoteIdentifier(): iterable
    {
        yield 'double quotes, column' => ['"', ['name'], '"name"'];
        yield 'double quotes, table and column' => ['"', ['t', 'name'], '"t"."name"'];
        yield 'double quotes, schema, table and column' => ['"', ['s', 't', 'name'], '"s"."t"."name"'];
        yield 'double quotes, part with dot' => ['"', ['t', 'na.me'], '"t"."na.me"'];
        yield 'double quotes, function call' => ['"', ['LOWER(name)'], '"LOWER(name)"'];
        yield 'double quotes, square brackets' => ['"', ['[[name]]'], '"[[name]]"'];
        yield 'double quotes, curly brackets with prefix' => ['"', ['{{%t}}', 'name'], '"{{%t}}"."name"'];
        yield 'double quotes, asterisk' => ['"', ['*'], '"*"'];
        yield 'double quotes, array index' => ['"', ['arr[1]'], '"arr[1]"'];
        yield 'double quotes, quote inside' => ['"', ['a"b'], '"a""b"'];
        yield 'double quotes, leading quote' => ['"', ['"name"'], '"""name"""'];
        yield 'double quotes, injection' => [
            '"',
            ['"a" DESC; DROP TABLE t; --'],
            '"""a"" DESC; DROP TABLE t; --"',
        ];

        yield 'backticks, column' => ['`', ['name'], '`name`'];
        yield 'backticks, table and column' => ['`', ['t', 'name'], '`t`.`name`'];
        yield 'backticks, schema, table and column' => ['`', ['s', 't', 'name'], '`s`.`t`.`name`'];
        yield 'backticks, part with dot' => ['`', ['t', 'na.me'], '`t`.`na.me`'];
        yield 'backticks, function call' => ['`', ['LOWER(name)'], '`LOWER(name)`'];
        yield 'backticks, square brackets' => ['`', ['[[name]]'], '`[[name]]`'];
        yield 'backticks, curly brackets with prefix' => ['`', ['{{%t}}', 'name'], '`{{%t}}`.`name`'];
        yield 'backticks, asterisk' => ['`', ['*'], '`*`'];
        yield 'backticks, array index' => ['`', ['arr[1]'], '`arr[1]`'];
        yield 'backticks, quote inside' => ['`', ['a`b'], '`a``b`'];
        yield 'backticks, leading quote' => ['`', ['`name`'], '```name```'];
        yield 'backticks, injection' => ['`', ['`a` DESC; DROP TABLE t; --'], '```a`` DESC; DROP TABLE t; --`'];

        yield 'square brackets, column' => [['[', ']'], ['name'], '[name]'];
        yield 'square brackets, table and column' => [['[', ']'], ['t', 'name'], '[t].[name]'];
        yield 'square brackets, schema, table and column' => [['[', ']'], ['s', 't', 'name'], '[s].[t].[name]'];
        yield 'square brackets, part with dot' => [['[', ']'], ['t', 'na.me'], '[t].[na.me]'];
        yield 'square brackets, function call' => [['[', ']'], ['LOWER(name)'], '[LOWER(name)]'];
        yield 'square brackets, square brackets' => [['[', ']'], ['[[name]]'], '[[[name]]]]]'];
        yield 'square brackets, curly brackets with prefix' => [['[', ']'], ['{{%t}}', 'name'], '[{{%t}}].[name]'];
        yield 'square brackets, asterisk' => [['[', ']'], ['*'], '[*]'];
        yield 'square brackets, array index' => [['[', ']'], ['arr[1]'], '[arr[1]]]'];
        yield 'square brackets, closing quote inside' => [['[', ']'], ['a]b'], '[a]]b]'];
        yield 'square brackets, opening quote inside' => [['[', ']'], ['a[b'], '[a[b]'];
        yield 'square brackets, leading quote' => [['[', ']'], ['[name]'], '[[name]]]'];
        yield 'square brackets, injection' => [
            ['[', ']'],
            ['[a] DESC; DROP TABLE t; --'],
            '[[a]] DESC; DROP TABLE t; --]',
        ];
    }

    #[DataProvider('dataQuoteIdentifier')]
    public function testQuoteIdentifier(string|array $quoteCharacter, array $parts, string $expected): void
    {
        $quoter = new Quoter($quoteCharacter, $quoteCharacter);

        $this->assertSame($expected, $quoter->quoteIdentifier(...$parts));
    }

    public function testQuoteIdentifierUsesColumnQuoteCharacter(): void
    {
        $quoter = new Quoter('`', '"');

        $this->assertSame('`t`.`name`', $quoter->quoteIdentifier('t', 'name'));
    }

    public function testQuoteIdentifierIgnoresTablePrefix(): void
    {
        $quoter = new Quoter('"', '"', 'pre_');

        $this->assertSame('"{{%t}}"."name"', $quoter->quoteIdentifier('{{%t}}', 'name'));
    }
}
