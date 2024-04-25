<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Schema;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Schema\Column\BooleanColumnSchema;
use Yiisoft\Db\Schema\Column\DoubleColumnSchema;
use Yiisoft\Db\Schema\Column\IntegerColumnSchema;
use Yiisoft\Db\Schema\Column\StringColumnSchema;
use Yiisoft\Db\Schema\SchemaInterface;
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
        $column = new ColumnSchema('new');

        $this->assertFalse($column->isAllowNull());

        $column->allowNull(true);

        $this->assertTrue($column->isAllowNull());

        $column->allowNull(false);

        $this->assertFalse($column->isAllowNull());
    }

    public function testAutoIncrement(): void
    {
        $column = new ColumnSchema('new');

        $this->assertFalse($column->isAutoIncrement());

        $column->autoIncrement(true);

        $this->assertTrue($column->isAutoIncrement());

        $column->autoIncrement(false);

        $this->assertFalse($column->isAutoIncrement());
    }

    public function testComment(): void
    {
        $column = new ColumnSchema('new');

        $this->assertNull($column->getComment());

        $column->comment('test');

        $this->assertSame('test', $column->getComment());

        $column->comment(null);

        $this->assertNull($column->getComment());
    }

    public function testComputed(): void
    {
        $column = new ColumnSchema('new');

        $this->assertFalse($column->isComputed());

        $column->computed(true);

        $this->assertTrue($column->isComputed());

        $column->computed(false);

        $this->assertFalse($column->isComputed());
    }

    public function testDbType(): void
    {
        $column = new ColumnSchema('new');

        $this->assertNull($column->getDbType());

        $column->dbType('test');

        $this->assertSame('test', $column->getDbType());

        $column->dbType(null);

        $this->assertNull($column->getDbType());
    }

    public function testDbTypecast(): void
    {
        $column = new ColumnSchema('new');

        $this->assertSame('', $column->dbTypecast(''));
    }

    public function testDefaultValue(): void
    {
        $column = new ColumnSchema('new');

        $this->assertNull($column->getDefaultValue());

        $column->defaultValue('test');

        $this->assertSame('test', $column->getDefaultValue());

        $column->defaultValue(null);

        $this->assertNull($column->getDefaultValue());
    }

    public function testEnumValues(): void
    {
        $column = new StringColumnSchema();

        $this->assertNull($column->getEnumValues());

        $column->enumValues(['positive', 'negative']);

        $this->assertSame(['positive', 'negative'], $column->getEnumValues());

        $column->enumValues([]);

        $this->assertSame([], $column->getEnumValues());
    }

    public function testExtra(): void
    {
        $column = new ColumnSchema('new');

        $this->assertNull($column->getExtra());

        $column->extra('test');

        $this->assertSame('test', $column->getExtra());

        $column->extra('');

        $this->assertSame('', $column->getExtra());
    }

    /**
     * @link https://github.com/yiisoft/db/issues/718
     */
    public function testTypecastIssue718(): void
    {
        $column = new ColumnSchema('new');

        $param = [1, 2];
        $result = $column->dbTypecast($param);
        $this->assertSame([1, 2], $result);
    }

    public function testName(): void
    {
        $column = new StringColumnSchema();

        $this->assertNull($column->getName());

        $column->name('test');

        $this->assertSame('test', $column->getName());

        $column->name('');

        $this->assertSame('', $column->getName());
    }

    public function testPhpType(): void
    {
        $column = new ColumnSchema('new');

        $this->assertNull($column->getPhpType());

        $column->phpType(SchemaInterface::PHP_TYPE_STRING);

        $this->assertSame(SchemaInterface::PHP_TYPE_STRING, $column->getPhpType());

        $column->phpType(null);

        $this->assertNull($column->getPhpType());
    }

    public function testPhpTypecast(): void
    {
        $column = new StringColumnSchema('new');

        $this->assertSame('test', $column->phpTypecast('test'));
    }

    public function testPhpTypecastWithBoolean(): void
    {
        $column = new BooleanColumnSchema('new');

        $this->assertTrue($column->phpTypecast(1));
    }

    public function testPhpTypecastWithDouble(): void
    {
        $column = new DoubleColumnSchema('new');

        $this->assertSame(1.2, $column->phpTypecast('1.2'));
    }

    public function testPhpTypecastWithInteger(): void
    {
        $column = new IntegerColumnSchema('new');

        $this->assertSame(1, $column->phpTypecast('1'));
    }

    public function testPhpTypecastWithStringBooleanValue(): void
    {
        self::markTestSkipped('Wrong test: database does not return bool value for string type');

        $column = new StringColumnSchema('new');

        $this->assertSame('1', $column->phpTypecast(true));
    }

    public function testPhpTypecastWithStringFloatValue(): void
    {
        self::markTestSkipped('Wrong test: database does not return double value for string type');

        $column = new StringColumnSchema('new');

        $this->assertSame('1.1', $column->phpTypecast(1.1));
    }

    public function testPhpTypecastWithStringIntegerValue(): void
    {
        self::markTestSkipped('Wrong test: database does not return int value for string type');

        $column = new StringColumnSchema('new');

        $this->assertSame('1', $column->phpTypecast(1));
    }

    public function testPhpTypecastWithStringNullValue(): void
    {
        $column = new StringColumnSchema('new');

        $this->assertNull($column->phpTypecast(null));
    }

    public function testPhpTypecastWithStringResourceValue(): void
    {
        $column = new StringColumnSchema('new');

        $this->assertIsResource($column->phpTypecast(fopen('php://memory', 'rb')));
    }

    public function testPrecision(): void
    {
        $column = new ColumnSchema('new');

        $this->assertNull($column->getPrecision());

        $column->precision(10);

        $this->assertSame(10, $column->getPrecision());

        $column->precision(0);

        $this->assertSame(0, $column->getPrecision());
    }

    public function testPrimaryKey(): void
    {
        $column = new ColumnSchema('new');

        $this->assertFalse($column->isPrimaryKey());

        $column->primaryKey(true);

        $this->assertTrue($column->isPrimaryKey());

        $column->primaryKey(false);

        $this->assertFalse($column->isPrimaryKey());
    }

    public function testScale(): void
    {
        $column = new ColumnSchema('new');

        $this->assertNull($column->getScale());

        $column->scale(10);

        $this->assertSame(10, $column->getScale());

        $column->scale(0);

        $this->assertSame(0, $column->getScale());
    }

    public function testSize(): void
    {
        $column = new ColumnSchema('new');

        $this->assertNull($column->getSize());

        $column->size(10);

        $this->assertSame(10, $column->getSize());

        $column->size(0);

        $this->assertSame(0, $column->getSize());
    }

    public function testType(): void
    {
        $column = new StringColumnSchema('');

        $this->assertSame('', $column->getType());

        $column->type('test');

        $this->assertSame('test', $column->getType());

        $column->type('');

        $this->assertSame('', $column->getType());
    }

    public function testUnsigned(): void
    {
        $column = new ColumnSchema('new');

        $this->assertFalse($column->isUnsigned());

        $column->unsigned(true);

        $this->assertTrue($column->isUnsigned());

        $column->unsigned(false);

        $this->assertFalse($column->isUnsigned());
    }
}
