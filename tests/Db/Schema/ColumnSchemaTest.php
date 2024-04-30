<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Schema;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Schema\Column\BooleanColumn;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\StringColumn;
use Yiisoft\Db\Schema\SchemaInterface;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ColumnSchemaTest extends TestCase
{
    public function testAllowNull(): void
    {
        $column = new StringColumn();

        $this->assertNull($column->isAllowNull());

        $column->allowNull();

        $this->assertTrue($column->isAllowNull());

        $column->allowNull(false);

        $this->assertFalse($column->isAllowNull());
    }

    public function testAutoIncrement(): void
    {
        $column = new StringColumn();

        $this->assertFalse($column->isAutoIncrement());

        $column->autoIncrement();

        $this->assertTrue($column->isAutoIncrement());

        $column->autoIncrement(false);

        $this->assertFalse($column->isAutoIncrement());
    }

    public function testComment(): void
    {
        $column = new StringColumn();

        $this->assertNull($column->getComment());

        $column->comment('test');

        $this->assertSame('test', $column->getComment());

        $column->comment(null);

        $this->assertNull($column->getComment());
    }

    public function testComputed(): void
    {
        $column = new StringColumn();

        $this->assertFalse($column->isComputed());

        $column->computed();

        $this->assertTrue($column->isComputed());

        $column->computed(false);

        $this->assertFalse($column->isComputed());
    }

    public function testDbType(): void
    {
        $column = new StringColumn();

        $this->assertNull($column->getDbType());

        $column->dbType('test');

        $this->assertSame('test', $column->getDbType());

        $column->dbType(null);

        $this->assertNull($column->getDbType());
    }

    public function testDbTypecast(): void
    {
        $column = new StringColumn();

        $this->assertSame('', $column->dbTypecast(''));
    }

    public function testDefaultValue(): void
    {
        $column = new StringColumn();

        $this->assertNull($column->getDefaultValue());

        $column->defaultValue('test');

        $this->assertSame('test', $column->getDefaultValue());

        $column->defaultValue(null);

        $this->assertNull($column->getDefaultValue());
    }

    public function testEnumValues(): void
    {
        $column = new StringColumn();

        $this->assertSame([], $column->getValues());

        $column->values(['positive', 'negative']);

        $this->assertSame(['positive', 'negative'], $column->getValues());

        $column->values([]);

        $this->assertSame([], $column->getValues());
    }

    public function testExtra(): void
    {
        $column = new StringColumn();

        $this->assertNull($column->getExtra());

        $column->extra('test');

        $this->assertSame('test', $column->getExtra());

        $column->extra('');

        $this->assertSame('', $column->getExtra());
    }

    public function testPhpType(): void
    {
        $column = new IntegerColumn();

        $this->assertSame(SchemaInterface::PHP_TYPE_INTEGER, $column->getPhpType());

        $column->phpType(SchemaInterface::PHP_TYPE_STRING);

        $this->assertSame(SchemaInterface::PHP_TYPE_STRING, $column->getPhpType());

        $column->phpType(null);

        $this->assertNull($column->getPhpType());
    }

    public function testPhpTypecast(): void
    {
        $column = new StringColumn();

        $this->assertSame('test', $column->phpTypecast('test'));
    }

    public function testPhpTypecastWithBoolean(): void
    {
        $column = new BooleanColumn();

        $this->assertTrue($column->phpTypecast(1));
    }

    public function testPhpTypecastWithDouble(): void
    {
        $column = new DoubleColumn();

        $this->assertSame(1.2, $column->phpTypecast('1.2'));
    }

    public function testPhpTypecastWithInteger(): void
    {
        $column = new IntegerColumn();

        $this->assertSame(1, $column->phpTypecast('1'));
    }

    public function testPhpTypecastWithStringBooleanValue(): void
    {
        self::markTestSkipped('Wrong test: database does not return bool value for string type');

        $column = new StringColumn();

        $this->assertSame('1', $column->phpTypecast(true));
    }

    public function testPhpTypecastWithStringFloatValue(): void
    {
        self::markTestSkipped('Wrong test: database does not return double value for string type');

        $column = new StringColumn();

        $this->assertSame('1.1', $column->phpTypecast(1.1));
    }

    public function testPhpTypecastWithStringIntegerValue(): void
    {
        self::markTestSkipped('Wrong test: database does not return int value for string type');

        $column = new StringColumn();

        $this->assertSame('1', $column->phpTypecast(1));
    }

    public function testPhpTypecastWithStringNullValue(): void
    {
        $column = new StringColumn();

        $this->assertNull($column->phpTypecast(null));
    }

    public function testPhpTypecastWithStringResourceValue(): void
    {
        $column = new StringColumn();

        $this->assertIsResource($column->phpTypecast(fopen('php://memory', 'rb')));
    }

    public function testPrimaryKey(): void
    {
        $column = new StringColumn();

        $this->assertFalse($column->isPrimaryKey());

        $column->primaryKey();

        $this->assertTrue($column->isPrimaryKey());

        $column->primaryKey(false);

        $this->assertFalse($column->isPrimaryKey());
    }

    public function testScale(): void
    {
        $column = new StringColumn();

        $this->assertNull($column->getScale());

        $column->scale(10);

        $this->assertSame(10, $column->getScale());

        $column->scale(0);

        $this->assertSame(0, $column->getScale());
    }

    public function testSize(): void
    {
        $column = new StringColumn();

        $this->assertNull($column->getSize());

        $column->size(10);

        $this->assertSame(10, $column->getSize());

        $column->size(0);

        $this->assertSame(0, $column->getSize());
    }

    public function testType(): void
    {
        $column = new StringColumn();

        $this->assertSame(SchemaInterface::PHP_TYPE_STRING, $column->getType());

        $column->type('test');

        $this->assertSame('test', $column->getType());

        $column->type('');

        $this->assertSame('', $column->getType());
    }

    public function testUnsigned(): void
    {
        $column = new StringColumn();

        $this->assertFalse($column->isUnsigned());

        $column->unsigned();

        $this->assertTrue($column->isUnsigned());

        $column->unsigned(false);

        $this->assertFalse($column->isUnsigned());
    }
}
