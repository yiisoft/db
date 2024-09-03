<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Schema;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Tests\Support\Stub\ColumnSchema;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ColumnSchemaTest extends TestCase
{
    public function testAllowNull(): void
    {
        $column = new ColumnSchema();

        $this->assertFalse($column->isAllowNull());
        $this->assertSame($column, $column->allowNull());
        $this->assertTrue($column->isAllowNull());

        $column->allowNull(false);

        $this->assertFalse($column->isAllowNull());

        $column->allowNull(true);

        $this->assertTrue($column->isAllowNull());
    }

    public function testAutoIncrement(): void
    {
        $column = new ColumnSchema();

        $this->assertFalse($column->isAutoIncrement());
        $this->assertSame($column, $column->autoIncrement());
        $this->assertTrue($column->isAutoIncrement());

        $column->autoIncrement(false);

        $this->assertFalse($column->isAutoIncrement());

        $column->autoIncrement(true);

        $this->assertTrue($column->isAutoIncrement());
    }

    public function testComment(): void
    {
        $column = new ColumnSchema();

        $this->assertNull($column->getComment());
        $this->assertSame($column, $column->comment('test'));
        $this->assertSame('test', $column->getComment());

        $column->comment(null);

        $this->assertNull($column->getComment());
    }

    public function testComputed(): void
    {
        $column = new ColumnSchema();

        $this->assertFalse($column->isComputed());
        $this->assertSame($column, $column->computed());
        $this->assertTrue($column->isComputed());

        $column->computed(false);

        $this->assertFalse($column->isComputed());

        $column->computed(true);

        $this->assertTrue($column->isComputed());
    }

    public function testDbType(): void
    {
        $column = new ColumnSchema();

        $this->assertNull($column->getDbType());
        $this->assertSame($column, $column->dbType('test'));
        $this->assertSame('test', $column->getDbType());

        $column->dbType(null);

        $this->assertNull($column->getDbType());
    }

    public function testDefaultValue(): void
    {
        $column = new ColumnSchema();

        $this->assertNull($column->getDefaultValue());
        $this->assertSame($column, $column->defaultValue('test'));
        $this->assertSame('test', $column->getDefaultValue());

        $column->defaultValue(null);

        $this->assertNull($column->getDefaultValue());
    }

    public function testEnumValues(): void
    {
        $column = new ColumnSchema();

        $this->assertNull($column->getEnumValues());
        $this->assertSame($column, $column->enumValues(['positive', 'negative']));
        $this->assertSame(['positive', 'negative'], $column->getEnumValues());

        $column->enumValues([]);

        $this->assertSame([], $column->getEnumValues());
    }

    public function testExtra(): void
    {
        $column = new ColumnSchema();

        $this->assertNull($column->getExtra());
        $this->assertSame($column, $column->extra('test'));
        $this->assertSame('test', $column->getExtra());

        $column->extra('');

        $this->assertSame('', $column->getExtra());
    }

    /** @dataProvider \Yiisoft\Db\Tests\Provider\ColumnSchemaProvider::load */
    public function testLoad(string $parameter, mixed $value, string $method, mixed $expected): void
    {
        $column = new ColumnSchema();

        $column->load([$parameter => $value]);

        $this->assertSame($expected, $column->$method());
    }

    public function testName(): void
    {
        $column = new ColumnSchema();

        $this->assertNull($column->getName());
        $this->assertSame($column, $column->name('test'));
        $this->assertSame('test', $column->getName());

        $column->name('');

        $this->assertSame('', $column->getName());
    }

    public function testPrecision(): void
    {
        $column = new ColumnSchema();

        $this->assertNull($column->getPrecision());
        $this->assertSame($column, $column->precision(10));
        $this->assertSame(10, $column->getPrecision());

        $column->precision(0);

        $this->assertSame(0, $column->getPrecision());
    }

    public function testPrimaryKey(): void
    {
        $column = new ColumnSchema();

        $this->assertFalse($column->isPrimaryKey());
        $this->assertSame($column, $column->primaryKey());
        $this->assertTrue($column->isPrimaryKey());

        $column->primaryKey(false);

        $this->assertFalse($column->isPrimaryKey());

        $column->primaryKey(true);

        $this->assertTrue($column->isPrimaryKey());
    }

    public function testScale(): void
    {
        $column = new ColumnSchema();

        $this->assertNull($column->getScale());
        $this->assertSame($column, $column->scale(10));
        $this->assertSame(10, $column->getScale());

        $column->scale(0);

        $this->assertSame(0, $column->getScale());
    }

    public function testSize(): void
    {
        $column = new ColumnSchema();

        $this->assertNull($column->getSize());
        $this->assertSame($column, $column->size(10));
        $this->assertSame(10, $column->getSize());

        $column->size(0);

        $this->assertSame(0, $column->getSize());
    }

    public function testType(): void
    {
        $column = new ColumnSchema();

        $this->assertSame('', $column->getType());
        $this->assertSame($column, $column->type('test'));
        $this->assertSame('test', $column->getType());

        $column->type('');

        $this->assertSame('', $column->getType());
    }

    public function testUnsigned(): void
    {
        $column = new ColumnSchema();

        $this->assertFalse($column->isUnsigned());
        $this->assertSame($column, $column->unsigned());
        $this->assertTrue($column->isUnsigned());

        $column->unsigned(false);

        $this->assertFalse($column->isUnsigned());

        $column->unsigned(true);

        $this->assertTrue($column->isUnsigned());
    }
}
